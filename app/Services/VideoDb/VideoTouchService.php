<?php

namespace App\Services\VideoDb;

use App\Services\VideoDb\Contracts\EnrichmentStrategyInterface;
use App\Services\VideoDb\DTOs\TouchConfigDto;

class VideoTouchService
{
    protected VideoDbApiClient $apiClient;
    protected VideoDbSyncService $syncService;
    protected array $enrichmentStrategies = [];

    protected $startTime;
    protected $logs = [];
    protected $timings = [];

    public function __construct(VideoDbApiClient $apiClient, VideoDbSyncService $syncService)
    {
        $this->apiClient = $apiClient;
        $this->syncService = $syncService;
    }

    public function registerEnrichment($key, EnrichmentStrategyInterface $strategy)
    {
        $this->enrichmentStrategies[$key] = $strategy;
        $this->syncService->registerEnrichment($key, $strategy);
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

    public function searchContent(TouchConfigDto $config)
    {
        $this->startTime = microtime(true);
        $this->logs = [];
        $this->timings = [];

        $errors = $config->validate();
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        try {
            $this->log("Searching for content in VideoDB...");

            $vdbId = $config->vdbId;

            if (empty($vdbId)) {
                $searchParams = [];
                if (!empty($config->imdbId)) {
                    $searchParams['imdb_id'] = $config->imdbId;
                }
                if (!empty($config->kinopoiskId)) {
                    $searchParams['kinopoisk_id'] = $config->kinopoiskId;
                }

                $searchResponse = $this->timeOperation('search_api', function() use ($config, $searchParams) {
                    return $this->apiClient->searchContent($config->contentType, $searchParams);
                });

                if (empty($searchResponse->results)) {
                    $this->log("No content found for the given identifier");
                    return [
                        'status' => 'not_found',
                        'message' => 'No content found in VideoDB for the given identifier',
                        'logs' => $this->logs,
                    ];
                }

                $content = $searchResponse->results[0];
                $vdbId = $content->id;
                $this->log("Found content: VDB ID = {$vdbId}");
            } else {
                $content = $this->timeOperation('get_content_api', function() use ($config, $vdbId) {
                    return $this->apiClient->getContentById($config->contentType, $vdbId);
                });
                $this->log("Fetched content by VDB ID: {$vdbId}");
            }

            $medias = $this->fetchMediasForContent($config->contentType, $vdbId, $content);
            $mediaCount = count($medias);
            $this->log("Found {$mediaCount} media files for this content");

            $totalTime = (microtime(true) - $this->startTime) * 1000;

            return [
                'status' => 'found',
                'content' => [
                    'vdb_id' => $vdbId,
                    'title' => $content->orig_title ?? null,
                    'ru_title' => $content->ru_title ?? null,
                    'imdb_id' => $content->imdb_id ?? null,
                    'kinopoisk_id' => $content->kinopoisk_id ?? null,
                    'type' => $config->contentType,
                    'media_count' => $mediaCount,
                ],
                'timings' => [
                    'total_ms' => round($totalTime),
                ],
                'logs' => $this->logs,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'logs' => $this->logs,
            ];
        }
    }

    public function touchVideo(TouchConfigDto $config)
    {
        $this->startTime = microtime(true);
        $this->logs = [];
        $this->timings = [];

        $errors = $config->validate();
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        try {
            $this->syncService->loadTranslationsCache();
            $this->log("Starting VideoTouch for {$config->contentType}...");

            $vdbId = $config->vdbId;
            $content = null;

            if (empty($vdbId)) {
                $searchParams = [];
                if (!empty($config->imdbId)) {
                    $searchParams['imdb_id'] = $config->imdbId;
                    $this->log("Searching by IMDB ID: {$config->imdbId}");
                }
                if (!empty($config->kinopoiskId)) {
                    $searchParams['kinopoisk_id'] = $config->kinopoiskId;
                    $this->log("Searching by Kinopoisk ID: {$config->kinopoiskId}");
                }

                $searchResponse = $this->timeOperation('search_api', function() use ($config, $searchParams) {
                    return $this->apiClient->searchContent($config->contentType, $searchParams);
                });

                if (empty($searchResponse->results)) {
                    $this->log("No content found for the given identifier");
                    return [
                        'status' => 'not_found',
                        'message' => 'No content found in VideoDB for the given identifier',
                        'logs' => $this->logs,
                    ];
                }

                $content = $searchResponse->results[0];
                $vdbId = $content->id;
                $this->log("Found content: VDB ID = {$vdbId}, Title = {$content->orig_title}");
            } else {
                $this->log("Using provided VDB ID: {$vdbId}");
                $content = $this->timeOperation('get_content_api', function() use ($config, $vdbId) {
                    return $this->apiClient->getContentById($config->contentType, $vdbId);
                });
                $this->log("Fetched content: Title = {$content->orig_title}");
            }

            $medias = $this->fetchMediasForContent($config->contentType, $vdbId, $content);
            $mediaCount = count($medias);
            $this->log("Fetched {$mediaCount} media files");

            if ($mediaCount === 0) {
                return [
                    'status' => 'no_media',
                    'message' => 'Content found but no media files available',
                    'content' => [
                        'vdb_id' => $vdbId,
                        'title' => $content->orig_title ?? null,
                        'ru_title' => $content->ru_title ?? null,
                    ],
                    'logs' => $this->logs,
                ];
            }

            $stats = [
                'new_videos' => 0,
                'updated_videos' => 0,
                'new_files' => 0,
                'files_processed' => 0,
                'errors' => 0,
                'enrichments_run' => 0,
            ];
            $processedVideos = [];
            $mediaErrors = [];

            foreach ($medias as $media) {
                try {
                    $result = $this->timeOperation('processing', function() use ($media, $config) {
                        return $this->syncService->processMedia($media, $config, true);
                    });

                    $stats['files_processed']++;

                    $videoId = $result['video_id'];
                    if ($videoId && !isset($processedVideos[$videoId])) {
                        $processedVideos[$videoId] = [
                            'video' => $result['video'],
                            'is_new' => $result['is_new'],
                            'was_updated' => $result['was_updated'],
                        ];
                        if ($result['is_new']) {
                            $stats['new_videos']++;
                        } elseif ($result['was_updated']) {
                            $stats['updated_videos']++;
                        }
                    }

                    if ($result['file_is_new']) {
                        $stats['new_files']++;
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $mediaErrors[] = [
                        'media_id' => $media->id,
                        'error' => $e->getMessage(),
                    ];
                    $this->log("Error processing media {$media->id}: " . $e->getMessage());
                }
            }

            foreach ($processedVideos as $videoId => $info) {
                $enriched = $this->runEnrichments($info['video'], $config);
                $stats['enrichments_run'] += $enriched;
            }

            $processedVideo = !empty($processedVideos) ? reset($processedVideos)['video'] : null;

            $totalTime = (microtime(true) - $this->startTime) * 1000;
            $this->log("VideoTouch completed in " . round($totalTime) . "ms");

            return [
                'status' => 'success',
                'content' => [
                    'vdb_id' => $vdbId,
                    'title' => $content->orig_title ?? null,
                    'ru_title' => $content->ru_title ?? null,
                    'type' => $config->contentType,
                ],
                'video' => $processedVideo ? [
                    'id' => $processedVideo->id,
                    'name' => $processedVideo->name,
                    'ru_name' => $processedVideo->ru_name,
                    'is_new' => $stats['new_videos'] > 0,
                    'was_updated' => $stats['updated_videos'] > 0,
                ] : null,
                'stats' => $stats,
                'errors' => $mediaErrors,
                'timings' => [
                    'search_api_ms' => round($this->timings['search_api'] ?? 0),
                    'medias_api_ms' => round($this->timings['medias_api'] ?? 0),
                    'seasons_api_ms' => round($this->timings['seasons_api'] ?? 0),
                    'processing_ms' => round($this->timings['processing'] ?? 0),
                    'total_ms' => round($totalTime),
                ],
                'logs' => $this->logs,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'logs' => $this->logs,
                'timings' => $this->timings,
            ];
        }
    }

    protected function fetchMediasForContent($contentType, $vdbId, $content)
    {
        if ($contentType === 'movie') {
            $mediasResponse = $this->timeOperation('medias_api', function() use ($vdbId) {
                return $this->apiClient->fetchMedias([
                    'vdb_id' => $vdbId,
                    'content_type' => 'movie',
                    'limit' => 1000,
                ]);
            });
            return $mediasResponse->results;
        }

        $seasonsResponse = $this->timeOperation('seasons_api', function() use ($contentType, $vdbId) {
            return $this->apiClient->fetchSeriesSeasons($contentType, $vdbId);
        });
        return $this->transformSeasonsToMedias($seasonsResponse, $content, $contentType);
    }

    protected function transformSeasonsToMedias($seasonsResponse, $seriesContent, $contentType)
    {
        $medias = [];
        $contentTypeMap = [
            'tvseries' => 'episode',
            'anime-tv-series' => 'animeepisode',
            'show-tv-series' => 'showepisode',
        ];
        $episodeContentType = $contentTypeMap[$contentType];

        if (empty($seasonsResponse->results)) {
            $this->log("No seasons found for series");
            return $medias;
        }

        foreach ($seasonsResponse->results as $season) {
            if (empty($season->episodes)) {
                $this->log("Season {$season->num} has no episodes");
                continue;
            }

            foreach ($season->episodes as $episode) {
                if (empty($episode->media)) {
                    $this->log("Episode S{$season->num}E{$episode->num} has no media");
                    continue;
                }

                foreach ($episode->media as $media) {
                    $contentObject = (object)[
                        'content_type' => $episodeContentType,
                        'id' => $episode->id,
                        'num' => $episode->num,
                        'orig_title' => $episode->orig_title,
                        'ru_title' => $episode->ru_title,
                        'tv_series' => (object)[
                            'id' => $seriesContent->id,
                            'orig_title' => $seriesContent->orig_title,
                            'ru_title' => $seriesContent->ru_title,
                            'kinopoisk_id' => $seriesContent->kinopoisk_id ?? null,
                            'imdb_id' => $seriesContent->imdb_id ?? null,
                            'season_count' => $seriesContent->season_count ?? null,
                            'start_date' => $seriesContent->start_date ?? null,
                        ],
                        'season' => (object)[
                            'num' => $season->num,
                        ],
                    ];

                    $mediaObj = clone $media;
                    $mediaObj->content_object = $contentObject;

                    $medias[] = $mediaObj;
                }
            }
        }

        $this->log("Transformed " . count($medias) . " media objects from seasons data");
        return $medias;
    }

    protected function runEnrichments($video, TouchConfigDto $config)
    {
        $count = 0;
        foreach ($config->getEnrichFlags() as $key => $enabled) {
            if ($enabled && isset($this->enrichmentStrategies[$key])) {
                try {
                    $strategy = $this->enrichmentStrategies[$key];
                    $this->timeOperation('enrichments', function() use ($strategy, $video) {
                        $strategy->enrich($video);
                    });
                    $count++;
                    $this->log("Enrichment {$key} applied to video {$video->id}");
                } catch (\Exception $e) {
                    $this->log("Enrichment {$key} failed for video {$video->id}: {$e->getMessage()}");
                }
            }
        }
        return $count;
    }
}
