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

class VideoDbSyncService
{
    protected $apiClient;
    protected $progressTracker;
    protected $enrichmentStrategies;

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
    }

    public function registerEnrichment($key, EnrichmentStrategyInterface $strategy)
    {
        $this->enrichmentStrategies[$key] = $strategy;
    }

    public function syncMedias(SyncConfigDto $configDto)
    {
        $startTime = microtime(true);

        if (!$this->progressTracker->acquireLock()) {
            $lockInfo = $this->progressTracker->isLocked();
            throw new \Exception(
                "Sync already in progress! Locked since " . $lockInfo['locked_since'] . " seconds ago. " .
                "TTL remaining: " . $lockInfo['ttl_remaining'] . " seconds. " .
                "Use /videodb/sync/reset to force unlock if needed."
            );
        }

        $this->progressTracker->start([
            'limit' => $configDto->limit,
            'offset' => $configDto->offset,
            'next_offset' => $configDto->offset,
            'config' => $configDto->toArray(),
        ]);

        $stats = [
            'processed' => $configDto->previouslyProcessed,
            'new_videos' => 0,
            'updated_videos' => 0,
            'enrichments_queued' => 0,
            'errors' => 0,
            'is_resume' => $configDto->isResume,
        ];

        try {
            echo "Starting to process medias...\n";

            foreach ($this->fetchMediasFromApi($configDto) as $index => $media) {
                try {
                    $result = $this->processMedia($media, $configDto);

                    if ($result['is_new']) {
                        $stats['new_videos']++;
                    } else if ($result['was_updated']) {
                        $stats['updated_videos']++;
                    }

                    $stats['processed']++;
                    $stats['enrichments_queued'] += $result['enrichments_queued'];

                    if (!$this->progressTracker->hasLock()) {
                        echo "Lock check failed - lock was released\n";
                        throw new SyncCancelledException('Sync was cancelled - lock was released');
                    }

                    $this->progressTracker->updateProgress([
                        'current' => $stats['processed'],
                        'total' => $stats['processed'],
                        'last_processed_id' => $media->id,
                    ]);

                    if (($stats['processed'] % 10) == 0) {
                        echo "Processed: {$stats['processed']}\n";
                    }

                } catch (SyncCancelledException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    echo "Error processing media {$media->id}: " . $e->getMessage() . "\n";
                    $this->progressTracker->addError([
                        'media_id' => $media->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            echo "Sync loop completed.\n";

            $duration = microtime(true) - $startTime;
            $this->progressTracker->complete($stats, $duration);

        } catch (SyncCancelledException $e) {
            echo "Sync cancelled: " . $e->getMessage() . "\n";
            throw $e;
        } catch (\Exception $e) {
            $this->progressTracker->fail($e->getMessage());
            throw $e;
        }

        return $stats;
    }

    protected function fetchMediasFromApi(SyncConfigDto $config)
    {
        $offset = $config->offset;
        $totalFetched = 0;

        $mode = $config->isResume ? 'RESUME' : 'FULL';
        echo "Starting {$mode} database sync (using Generator for memory efficiency)...\n";
        if ($config->isResume) {
            echo "Resuming from offset: {$offset}\n";
        }

        while (true) {
            if (!$this->progressTracker->hasLock()) {
                echo "Lock released - stopping sync\n";
                throw new SyncCancelledException('Sync was cancelled - lock was released');
            }

            $requestStart = microtime(true);
            $response = $this->apiClient->fetchMedias([
                'limit' => $config->limit,
                'offset' => $offset,
                'ordering' => 'created',
                'vdb_id' => $config->vdbId,
                'extra_params' => $config->extraParams,
            ]);

            $batchCount = count($response->results);
            $totalFetched += $batchCount;

            echo "API call duration: " . (microtime(true) - $requestStart) . "s\n";
            echo "Fetched batch: offset={$offset} count={$batchCount} (total: {$totalFetched})\n";

            $this->progressTracker->updateProgress([
                'next_offset' => $offset + $batchCount,
            ]);

            foreach ($response->results as $media) {
                yield $media;
            }

            if ($batchCount < $config->limit) {
                echo "Reached end of database. Total fetched: {$totalFetched}\n";
                break;
            }

            if (!empty($config->vdbId)) {
                break;
            }

            $offset += $config->limit;
        }
    }

    protected function processMedia($media, SyncConfigDto $config)
    {
        $isNew = false;
        $wasUpdated = false;
        $enrichmentsQueued = 0;

        $resolutions = $this->extractResolutions($media->qualities);

        $translation = Translation::updateOrCreate(
            ['id_VDB' => $media->translation->id],
            ['title' => $media->translation->title]
        );

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
            echo "Unknown content type: {$contentType}\n";
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
            $video = new Video($newData);
            $video->save();
            $isNew = true;
            echo "Created new video: {$video->id} (VDB: {$contentObj->id}, type: {$contentType})\n";
        } else {
            if ($this->shouldUpdateVideo($video, $newData)) {
                $video->fill($newData);
                $video->updated_at = date('Y-m-d H:i:s');
                $video->save();
                $wasUpdated = true;
                echo "Updated video: {$video->id} (VDB: {$contentObj->id}, type: {$contentType})\n";
            } else {
                echo "Video unchanged: {$video->id} (VDB: {$contentObj->id}, type: {$contentType})\n";
            }
        }

        $vdbFileIds = [$media->id];

        $this->cleanupOrphanedFiles($video, $vdbFileIds);

        $file = File::where('id_VDB', $media->id)
            ->where('sids', 'VDB')
            ->first();

        if (empty($file)) {
            $file = File::create([
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

            $wasUpdated = true;

            echo "Created file: {$file->id} (VDB: {$media->id})\n";
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
            $video = new Video($newData);
            $video->save();
            $isNew = true;
            echo "Created new series: {$video->id} (VDB: {$tvSeriesId}, type: {$contentType})\n";
        } else {
            if ($this->shouldUpdateVideo($video, $newData)) {
                $video->fill($newData);
                $video->updated_at = date('Y-m-d H:i:s');
                $video->save();
                $wasUpdated = true;
                echo "Updated series: {$video->id}\n";
            }
        }

        $file = File::where('id_VDB', $media->id)
            ->where('sids', 'VDB')
            ->first();

        if (empty($file)) {
            $file = File::create([
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
                echo "  Field '{$field}' changed: '{$oldValue}' → '{$newValue}'\n";
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
                echo "  Removing orphaned file: {$file->id} (VDB: {$file->id_VDB})\n";

                Screenshot::where('id_file', $file->id)->delete();
                Subtitle::where('file_id', $file->id)->delete();

                $file->delete();
                $removedCount++;
            }
        }

        if ($removedCount > 0) {
            echo "  Cleaned up {$removedCount} orphaned file(s)\n";
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
                        $strategy->enrich($video);
                        $video->save();
                    }
                } catch (\Exception $e) {
                    echo "Enrichment {$key} failed for video {$video->id}: {$e->getMessage()}\n";
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
        $keyWin = Seting::where('name', 'keyWin')->first()->value;

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $date = '2055010101';
        $hash = md5("{$path}--{$date}-{$keyWin}");

        return "https://{$host}/{$hash}:{$date}{$path}";
    }
}
