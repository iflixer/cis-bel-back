<?php

namespace App\Services\VideoDb;

use App\Services\VideoDb\Contracts\EnrichmentStrategyInterface;
use App\Services\VideoDb\DTOs\SyncConfigDto;
use App\Jobs\VideoDb\EnrichVideoJob;
use App\Video;
use App\File;
use App\Translation;
use App\Screenshot;
use App\Subtitle;
use App\Seting;
use Queue;
use DB;

class VideoDbSyncService
{
    protected VideoDbApiClient $apiClient;
    protected SyncProgressTracker $progressTracker;
    protected array $enrichmentStrategies;
    protected array $cachedSettings = [];

    protected $translationsCache = null;

    protected $startTime;
    protected $logs = [];
    protected $timings = [];

    const VIDEO_SYNC_FIELDS = [
        'name',
        'ru_name',
        'kinopoisk',
        'imdb',
        'quality',
        'year',
        'description',
    ];

    public function __construct(VideoDbApiClient $apiClient, SyncProgressTracker $progressTracker)
    {
        $this->apiClient = $apiClient;
        $this->progressTracker = $progressTracker;
        $this->enrichmentStrategies = [];
        $this->loadCachedSettings();
    }

    protected function loadCachedSettings()
    {
        $this->cachedSettings = Seting::whereIn('name', ['keyWin'])
            ->pluck('value', 'name')
            ->toArray();
    }

    protected function loadTranslationsCache()
    {
        $this->translationsCache = Translation::all()->keyBy('id_VDB');
        $this->log("Loaded " . $this->translationsCache->count() . " translations into cache");
    }

    protected function getOrCreateTranslation($vdbTranslation)
    {
        $vdbId = $vdbTranslation->id;

        if ($this->translationsCache && $this->translationsCache->has($vdbId)) {
            return $this->translationsCache->get($vdbId);
        }

        $translation = Translation::updateOrCreate(
            ['id_VDB' => $vdbId],
            ['title' => $vdbTranslation->title]
        );

        if ($this->translationsCache) {
            $this->translationsCache->put($vdbId, $translation);
        }

        return $translation;
    }

    public function registerEnrichment($key, EnrichmentStrategyInterface $strategy)
    {
        $this->enrichmentStrategies[$key] = $strategy;
    }

    protected function log($message)
    {
        $elapsed = microtime(true) - $this->startTime;
        $this->logs[] = sprintf("[%.2fs] %s", $elapsed, $message);
    }

    protected function timeOperation($name, callable $fn)
    {
        $start = microtime(true);
        $result = $fn();
        $duration = (microtime(true) - $start) * 1000;
        $this->timings[$name] = ($this->timings[$name] ?? 0) + $duration;
        return $result;
    }

    public function syncBatch(SyncConfigDto $configDto)
    {
        $this->startTime = microtime(true);
        $this->logs = [];
        $this->timings = [];

        $this->log("Starting batch sync (sort={$configDto->sortDirection}, offset={$configDto->offset}, limit={$configDto->limit})");
        if (!$this->progressTracker->acquireLock()) {
            $lockInfo = $this->progressTracker->isLocked();
            return [
                'status' => 'error',
                'error' => 'Another batch is in progress for this sort direction',
                'lock_info' => $lockInfo,
            ];
        }

        try {
            $this->loadTranslationsCache();
            $this->log("Fetching batch from VideoDB API...");
            $response = $this->timeOperation('api_request', function() use ($configDto) {
                return $this->apiClient->fetchMedias([
                    'limit' => $configDto->limit,
                    'offset' => $configDto->offset,
                    'ordering' => $configDto->sortDirection,
                    'vdb_id' => $configDto->vdbId,
                    'extra_params' => $configDto->extraParams,
                ]);
            });

            $batchCount = count($response->results);
            $this->log("API: Fetched {$batchCount} records");

            $stats = [
                'new_videos' => 0,
                'updated_videos' => 0,
                'unchanged' => 0,
                'errors' => 0,
                'enrichments_run' => 0,
            ];
            $errors = [];

            foreach ($response->results as $media) {
                try {
                    $result = $this->timeOperation('processing', function() use ($media, $configDto) {
                        return $this->processMedia($media, $configDto);
                    });

                    if ($result['is_new']) {
                        $stats['new_videos']++;
                    } elseif ($result['was_updated']) {
                        $stats['updated_videos']++;
                    } else {
                        $stats['unchanged']++;
                    }

                    $stats['enrichments_run'] += $result['enrichments_queued'];

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $errors[] = [
                        'media_id' => $media->id,
                        'error' => $e->getMessage(),
                    ];
                    $this->log("Error processing media {$media->id}: " . $e->getMessage());
                }
            }

            $newOffset = $configDto->offset + $batchCount;
            $currentProgress = $this->progressTracker->getProgress();
            $previousTotal = $currentProgress ? $currentProgress['total_processed'] : 0;
            $newTotalProcessed = $previousTotal + $batchCount;

            $this->progressTracker->updateProgress($newOffset, $batchCount);

            $hasMore = $batchCount >= $configDto->limit;
            $cycleCompleted = false;

            if ($configDto->maxRecords !== null && $newTotalProcessed >= $configDto->maxRecords) {
                $this->log("Max records limit reached ({$configDto->maxRecords}). Clearing progress.");
                $this->progressTracker->clearProgress();
                $cycleCompleted = true;
                $hasMore = false;
            }

            if (!$hasMore && !$cycleCompleted) {
                $this->log("End of database reached. Clearing progress.");
                $this->progressTracker->clearProgress();
            }

            $totalTime = (microtime(true) - $this->startTime) * 1000;
            $this->log("Batch completed in " . round($totalTime) . "ms");

            $this->progressTracker->releaseLock();


            return [
                'status' => $cycleCompleted ? 'cycle_completed' : ($hasMore ? 'batch_completed' : 'sync_completed'),
                'sort_direction' => $configDto->sortDirection,
                'batch' => [
                    'offset' => $configDto->offset,
                    'limit' => $configDto->limit,
                    'fetched' => $batchCount,
                    'processed' => $batchCount,
                ],
                'totals' => [
                    'total_processed' => $newTotalProcessed,
                    'max_records' => $configDto->maxRecords,
                    'progress_percent' => $configDto->maxRecords
                        ? round(($newTotalProcessed / $configDto->maxRecords) * 100, 2)
                        : null,
                ],
                'stats' => $stats,
                'errors' => $errors,
                'timings' => [
                    'api_request_ms' => round($this->timings['api_request'] ?? 0),
                    'processing_ms' => round($this->timings['processing'] ?? 0),
                    'inserts_ms' => round($this->timings['inserts'] ?? 0),
                    'updates_ms' => round($this->timings['updates'] ?? 0),
                    'total_ms' => round($totalTime),
                ],
                'logs' => $this->logs,
                'next' => [
                    'offset' => $hasMore ? $newOffset : 0,
                    'has_more' => $hasMore,
                    'call_url' => $hasMore
                        ? "/videodb/sync?resume=1&sort={$configDto->sortDirection}"
                        : null,
                ],
            ];

        } catch (\Exception $e) {
            $this->progressTracker->releaseLock();

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'logs' => $this->logs,
                'timings' => $this->timings,
            ];
        }
    }

    protected function processMedia($media, SyncConfigDto $config)
    {
        $isNew = false;
        $wasUpdated = false;
        $enrichmentsQueued = 0;

        $resolutions = $this->extractResolutions($media->qualities);

        $translation = $this->getOrCreateTranslation($media->translation);

        $contentType = $media->content_object->content_type;

        if (in_array($contentType, ['movie', 'anime', 'show'])) {
            $result = $this->processMovie($media, $translation, $resolutions, $config);
            $isNew = $result['is_new'];
            $wasUpdated = $result['was_updated'];
            $video = $result['video'];
        } elseif (in_array($contentType, ['episode', 'animeepisode', 'showepisode'])) {
            $result = $this->processEpisode($media, $translation, $resolutions, $config);
            $isNew = $result['is_new'];
            $wasUpdated = $result['was_updated'];
            $video = $result['video'];
        } else {
            $this->log("Unknown content type: {$contentType}");
            return ['is_new' => false, 'was_updated' => false, 'enrichments_queued' => 0];
        }

        if ($video) {
            $enrichmentsQueued = $this->handleEnrichments($video, $config);
        }

        return [
            'is_new' => $isNew,
            'was_updated' => $wasUpdated,
            'enrichments_queued' => $enrichmentsQueued,
        ];
    }

    protected function processMovie($media, $translation, $resolutions, SyncConfigDto $config)
    {
        $contentObj = $media->content_object;
        $contentType = $contentObj->content_type;

        $video = Video::where('id_VDB', $contentObj->id)
            ->where('tupe', $contentType)
            ->first();

        $newData = [
            'id_VDB' => $contentObj->id,
            'tupe' => $contentType,
            'name' => $contentObj->orig_title,
            'ru_name' => $contentObj->ru_title,
            'kinopoisk' => $contentObj->kinopoisk_id ?? null,
            'quality' => "{$media->source_quality} {$media->max_quality}",
        ];

        $isNew = false;
        $wasUpdated = false;

        if (empty($video)) {
            $video = $this->timeOperation('inserts', function() use ($newData) {
                $v = new Video($newData);
                $v->save();
                return $v;
            });
            $isNew = true;
            $this->log("Created new video: {$video->id} (VDB: {$contentObj->id}, type: {$contentType})");
        } else {
            if ($this->shouldUpdateVideo($video, $newData)) {
                $this->timeOperation('updates', function() use ($video, $newData) {
                    $video->fill($newData);
                    $video->updated_at = date('Y-m-d H:i:s');
                    $video->save();
                });
                $wasUpdated = true;
                $this->log("Updated video: {$video->id} (VDB: {$contentObj->id}, type: {$contentType})");
            }
        }

        $vdbFileIds = [$media->id];

        $this->cleanupOrphanedFiles($video, $vdbFileIds);

        $file = File::where('id_VDB', $media->id)
            ->where('sids', 'VDB')
            ->first();

        if (empty($file)) {
            $file = $this->timeOperation('inserts', function() use ($media, $video, $contentObj, $translation, $resolutions) {
                return File::create([
                    'id_VDB' => $media->id,
                    'id_parent' => $video->id,
                    'path' => $media->path,
                    'name' => $contentObj->orig_title,
                    'ru_name' => $contentObj->ru_title,
                    'season' => 0,
                    'resolutions' => $resolutions,
                    'num' => 0,
                    'translation_id' => $translation->id,
                    'translation' => $media->translation->title,
                    'sids' => 'VDB',
                ]);
            });

            $wasUpdated = true;
            $this->log("Created file: {$file->id} (VDB: {$media->id})");
        }

        $this->processScreenshots($media, $file, $video, $config);
        $this->processSubtitles($media, $file);

        return ['video' => $video, 'is_new' => $isNew, 'was_updated' => $wasUpdated];
    }

    protected function processEpisode($media, $translation, $resolutions, SyncConfigDto $config)
    {
        $contentObj = $media->content_object;
        $tvSeriesId = $contentObj->tv_series->id;
        $contentType = $contentObj->content_type;

        $video = Video::where('id_VDB', $tvSeriesId)
            ->where('tupe', $contentType)
            ->first();

        $newData = [
            'id_VDB' => $tvSeriesId,
            'tupe' => $contentType,
            'name' => $contentObj->tv_series->orig_title,
            'ru_name' => $contentObj->tv_series->ru_title,
            'kinopoisk' => $contentObj->kinopoisk_id ?? null,
            'quality' => "{$media->source_quality} {$media->max_quality}",
        ];

        $isNew = false;
        $wasUpdated = false;

        if (empty($video)) {
            $video = $this->timeOperation('inserts', function() use ($newData) {
                $v = new Video($newData);
                $v->save();
                return $v;
            });
            $isNew = true;
            $this->log("Created new series: {$video->id} (VDB: {$tvSeriesId}, type: {$contentType})");
        } else {
            if ($this->shouldUpdateVideo($video, $newData)) {
                $this->timeOperation('updates', function() use ($video, $newData) {
                    $video->fill($newData);
                    $video->updated_at = date('Y-m-d H:i:s');
                    $video->saveOrFail();
                });
                $wasUpdated = true;
                $this->log("Updated series: {$video->id}");
            }
        }

        $file = File::where('id_VDB', $media->id)
            ->where('sids', 'VDB')
            ->first();

        if (empty($file)) {
            $file = $this->timeOperation('inserts', function() use ($media, $video, $contentObj, $translation, $resolutions) {
                return File::create([
                    'id_VDB' => $media->id,
                    'id_parent' => $video->id,
                    'path' => $media->path,
                    'name' => $contentObj->orig_title,
                    'ru_name' => $contentObj->ru_title,
                    'season' => $contentObj->season->num,
                    'resolutions' => $resolutions,
                    'num' => $contentObj->num,
                    'translation_id' => $translation->id,
                    'translation' => $media->translation->title,
                    'sids' => 'VDB',
                ]);
            });

            $wasUpdated = true;
        }

        $this->processScreenshots($media, $file, $video, $config);
        $this->processSubtitles($media, $file);

        return ['video' => $video, 'is_new' => $isNew, 'was_updated' => $wasUpdated];
    }

    protected function shouldUpdateVideo(Video $video, array $newData)
    {
        foreach (self::VIDEO_SYNC_FIELDS as $field) {
            if (!isset($newData[$field])) continue;

            $oldValue = $video->$field;
            $newValue = $newData[$field];

            if ($oldValue != $newValue) {
                $this->log("  Field '{$field}' changed: '{$oldValue}' → '{$newValue}'");
                return true;
            }
        }

        return false;
    }

    protected function cleanupOrphanedFiles(Video $video, array $vdbFileIds)
    {
        $existingFiles = File::where('id_parent', $video->id)
            ->where('sids', 'VDB')
            ->get();

        $removedCount = 0;

        foreach ($existingFiles as $file) {
            if (!in_array($file->id_VDB, $vdbFileIds)) {
                $this->log("  Removing orphaned file: {$file->id} (VDB: {$file->id_VDB})");

                Screenshot::where('id_file', $file->id)->delete();
                Subtitle::where('file_id', $file->id)->delete();

                $file->delete();
                $removedCount++;
            }
        }

        if ($removedCount > 0) {
            $this->log("  Cleaned up {$removedCount} orphaned file(s)");
        }
    }

    protected function processScreenshots($media, $file, $video, SyncConfigDto $config)
    {
        if (!$config->forceImport) {
            $ssCount = Screenshot::where('id_file', $file->id)->count();
            if ($ssCount > 0) {
                return;
            }
        }

        if (empty($media->screens)) {
            return;
        }

        $firstScreenshot = '';
        foreach ($media->screens as $i => $screenUrl) {
            $protectedUrl = $this->makeProtectedScreenshotUrl($screenUrl);

            Screenshot::updateOrCreate(
                ['id_file' => $file->id, 'num' => $i],
                ['url' => $protectedUrl]
            );

            if ($i == 1) {
                $firstScreenshot = $protectedUrl;
            }
        }

        if (empty($video->backdrop) && !empty($firstScreenshot)) {
            $video->backdrop = $firstScreenshot;
            $video->save();
        }
    }

    protected function processSubtitles($media, $file)
    {
        if (empty($media->subtitles)) {
            return;
        }

        foreach ($media->subtitles as $subtitle) {
            Subtitle::updateOrCreate(
                [
                    'file_id' => $file->id,
                    'track_num' => $subtitle->track_num,
                ],
                [
                    'lang' => $subtitle->lang,
                    'subtitle_type' => $subtitle->subtitle_type,
                    'filename' => $subtitle->file,
                    'url' => $subtitle->url,
                ]
            );
        }
    }

    protected function handleEnrichments(Video $video, SyncConfigDto $config)
    {
        $queued = 0;

        if ($config->useJobs) {
            foreach ($config->enrichFlags as $key => $enabled) {
                if ($enabled && isset($this->enrichmentStrategies[$key])) {
                    Queue::push(new EnrichVideoJob($video->id, $key));
                    $queued++;
                }
            }

            return $queued;
        }

        foreach ($config->enrichFlags as $key => $enabled) {
            if ($enabled && isset($this->enrichmentStrategies[$key])) {
                try {
                    $strategy = $this->enrichmentStrategies[$key];
                    if ($strategy->shouldEnrich($video, $config->forceImport)) {
                        $this->timeOperation('enrichments', function() use ($strategy, $video) {
                            $strategy->enrich($video);
                            $video->save();
                        });
                        $queued++;
                        $this->log("Enrichment {$key} applied to video {$video->id}");
                    }
                } catch (\Exception $e) {
                    $this->log("Enrichment {$key} failed for video {$video->id}: {$e->getMessage()}");
                }
            }
        }

        return $queued;
    }

    protected function extractResolutions($qualities)
    {
        if (empty($qualities)) {
            return '';
        }

        $resolutions = [];
        foreach ($qualities as $quality) {
            $resolutions[] = $quality->resolution;
        }

        return implode(',', $resolutions);
    }

    protected function makeProtectedScreenshotUrl($url)
    {
        $keyWin = $this->cachedSettings['keyWin'] ?? null;
        if (!$keyWin) {
            throw new \Exception('keyWin setting not found in cache');
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $date = '2055010101';
        $hash = md5("{$path}--{$date}-{$keyWin}");

        return "https://{$host}/{$hash}:{$date}{$path}";
    }
}
