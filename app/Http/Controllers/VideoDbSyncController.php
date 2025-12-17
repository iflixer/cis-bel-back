<?php

namespace App\Http\Controllers;

use App\Services\VideoDb\DTOs\SyncConfigDto;
use App\Services\VideoDb\VideoDbSyncService;
use App\Services\VideoDb\VideoDbApiClient;
use App\Services\VideoDb\SyncProgressTracker;
use App\Services\VideoDb\Enrichments\KinopoiskEnrichment;
use App\Services\VideoDb\Enrichments\TmdbEnrichment;
use App\Services\VideoDb\Enrichments\ThetvdbEnrichment;
use App\Services\VideoDb\Enrichments\FanartEnrichment;
use App\Services\VideoDb\Enrichments\OpenaiEnrichment;
use App\Services\VideoDb\Enrichments\KinopoiskDevEnrichment;
use Illuminate\Http\Request;

class VideoDbSyncController extends Controller
{
    /**
     * Main sync endpoint - processes ONE batch per request
     * GET /videodb/sync?sort=created&max_records=10000
     * GET /videodb/sync?resume=1&sort=created - continue from last offset
     */
    public function sync(Request $request)
    {
        $sortDirection = $request->input('sort', 'created');

        if (!in_array($sortDirection, ['created', '-accepted'])) {
            return response()->json([
                'status' => 'error',
                'error' => 'Invalid sort direction. Must be "created" or "accepted".',
            ], 400);
        }

        $progressTracker = new SyncProgressTracker($sortDirection);
        $lockInfo = $progressTracker->isLocked();
        if ($lockInfo) {
            return response()->json([
                'status' => 'error',
                'error' => 'Another batch is in progress for this sort direction',
                'sort_direction' => $sortDirection,
                'lock_info' => $lockInfo,
            ], 409);
        }

        // Create sync service with this progress tracker
        $apiClient = new VideoDbApiClient();
        $syncService = new VideoDbSyncService($apiClient, $progressTracker);

        $syncService->registerEnrichment('kinopoisk', new KinopoiskEnrichment());
        $syncService->registerEnrichment('tmdb', new TmdbEnrichment());
        $syncService->registerEnrichment('thetvdb', new ThetvdbEnrichment());
        $syncService->registerEnrichment('fanart', new FanartEnrichment());
        $syncService->registerEnrichment('openai', new OpenaiEnrichment());
        $syncService->registerEnrichment('kinopoiskdev', new KinopoiskDevEnrichment());

        // Build config
        $resume = (bool) $request->input('resume', false);
        $force = (bool) $request->input('force', false);

        if ($force) {
            $progressTracker->clearProgress();
        }

        $config = $this->buildConfig($request, $progressTracker, $resume);

        $result = $syncService->syncBatch($config);

        return response()->json($result, $result['status'] === 'error' ? 500 : 200);
    }


    protected function buildConfig(Request $request, SyncProgressTracker $progressTracker, $resume)
    {
        $sortDirection = $request->input('sort', 'created');
        $limit = (int) $request->input('limit', 100);
        $maxRecords = $request->input('max_records') ? (int) $request->input('max_records') : null;

        $offset = 0;
        if ($resume) {
            $resumeState = $progressTracker->getResumeState();
            if ($resumeState) {
                $offset = $resumeState['offset'];
                if ($maxRecords === null && $resumeState['max_records'] !== null) {
                    $maxRecords = $resumeState['max_records'];
                }
            }
        } else {
            $offset = (int) $request->input('offset', 0);
        }

        if (!$progressTracker->hasProgress()) {
            $progressTracker->initProgress([
                'sort_direction' => $sortDirection,
                'limit' => $limit,
                'max_records' => $maxRecords,
            ]);
        }

        $vdbId = $request->input('vdb_id');
        $extraParams = $request->input('extra_vdb_parameters', '');
        $forceImport = (bool) $request->input('force_import_extra', false);

        $enrichFlags = [
            'kinopoisk' => (bool) $request->input('enrich_kp', false),
            'tmdb' => (bool) $request->input('enrich_tmdb', false),
            'thetvdb' => (bool) $request->input('enrich_thetvdb', false),
            'fanart' => (bool) $request->input('enrich_fanart', false),
            'openai' => (bool) $request->input('enrich_openai', false),
            'kinopoiskdev' => (bool) $request->input('enrich_kinopoiskdev', false),
        ];

        $useJobs = (bool) $request->input('use_jobs', false);

        return SyncConfigDto::fromArray([
            'limit' => $limit,
            'offset' => $offset,
            'vdb_id' => $vdbId,
            'extra_params' => $extraParams,
            'force_import' => $forceImport,
            'enrich_flags' => $enrichFlags,
            'use_jobs' => $useJobs,
            'sort_direction' => $sortDirection,
            'max_records' => $maxRecords,
        ]);
    }

    /**
     * Get current sync progress
     * GET /videodb/sync/progress?sort=created
     */
    public function progress(Request $request)
    {
        $sortDirection = $request->input('sort', 'created');

        if (!in_array($sortDirection, ['created', '-accepted'])) {
            return response()->json([
                'error' => 'Invalid sort direction',
            ], 400);
        }

        $progressTracker = new SyncProgressTracker($sortDirection);

        $lockInfo = $progressTracker->isLocked();
        $progress = $progressTracker->getProgress();

        if (!$progress) {
            return response()->json([
                'status' => 'no_progress',
                'sort_direction' => $sortDirection,
                'lock_status' => $lockInfo ? 'locked' : 'unlocked',
                'lock_info' => $lockInfo,
                'message' => 'No active sync found for this sort direction',
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'sort_direction' => $sortDirection,
            'lock_status' => $lockInfo ? 'locked' : 'unlocked',
            'lock_info' => $lockInfo,
            'progress' => $progress,
        ]);
    }

    /**
     * Reset sync lock and progress
     * POST /videodb/sync/reset?sort=created
     */
    public function reset(Request $request)
    {
        $sortDirection = $request->input('sort', 'created');

        if (!in_array($sortDirection, ['created', '-accepted'])) {
            return response()->json([
                'error' => 'Invalid sort direction',
            ], 400);
        }

        $progressTracker = new SyncProgressTracker($sortDirection);

        $lockInfo = $progressTracker->isLocked();
        $progress = $progressTracker->getProgress();

        if (!$lockInfo && !$progress) {
            return response()->json([
                'status' => 'ok',
                'sort_direction' => $sortDirection,
                'message' => 'No active lock or progress found. Nothing to reset.',
            ]);
        }

        $result = [
            'status' => 'ok',
            'sort_direction' => $sortDirection,
            'cleared' => [],
        ];

        if ($lockInfo) {
            $result['cleared'][] = 'lock';
            $result['previous_lock'] = $lockInfo;
        }

        if ($progress) {
            $result['cleared'][] = 'progress';
            $result['previous_progress'] = $progress;
        }

        $progressTracker->forceResetLock();

        $result['message'] = 'Lock and progress cleared successfully. You can now start a new sync.';

        return response()->json($result);
    }

    public function health(Request $request)
    {
        $sortDirection = $request->input('sort');
        if ($sortDirection) {
            if (!in_array($sortDirection, ['created', '-accepted'])) {
                return response()->json(['error' => 'Invalid sort direction'], 400);
            }
            $result = $this->getDirectionHealth($sortDirection);
            $httpCode = $result['status'] === 'ok' ? 200 : 500;
            return response()->json($result, $httpCode);
        }

        $results = [];
        $overallStatus = 'ok';
        $httpCode = 200;

        foreach (['created', '-accepted'] as $direction) {
            $result = $this->getDirectionHealth($direction);
            $results[$direction] = $result;

            if ($result['status'] !== 'ok') {
                $overallStatus = 'error';
                $httpCode = 500;
            }
        }

        return response()->json([
            'status' => $overallStatus,
            'directions' => $results,
            'checked_at' => time(),
        ], $httpCode);
    }

    protected function getDirectionHealth($sortDirection)
    {
        $progressTracker = new SyncProgressTracker($sortDirection);
        $healthData = $progressTracker->getHealthStatus();
        $lockInfo = $progressTracker->isLocked();

        $maxSuccessAge = 600;
        $maxConsecutiveErrors = 3;
        $staleLockSeconds = 180;

        $now = time();

        if (!$healthData || !isset($healthData['last_success_at'])) {
            return [
                'status' => 'error',
                'message' => 'No sync history found',
                'health_data' => $healthData,
            ];
        }

        $successAge = $now - $healthData['last_success_at'];
        $consecutiveErrors = $healthData['consecutive_errors'];
        $lockAge = $lockInfo ? ($now - (int)$lockInfo['locked_at']) : 0;
        $staleLock = $lockInfo && $lockAge > $staleLockSeconds;

        if ($successAge > $maxSuccessAge || $consecutiveErrors >= $maxConsecutiveErrors || $staleLock) {
            $issues = [];
            if ($successAge > $maxSuccessAge) $issues[] = "No success in {$successAge}s";
            if ($consecutiveErrors >= $maxConsecutiveErrors) $issues[] = "{$consecutiveErrors} consecutive errors";
            if ($staleLock) $issues[] = "Stale lock ({$lockAge}s)";

            return [
                'status' => 'error',
                'message' => implode('; ', $issues),
                'seconds_since_success' => $successAge,
                'consecutive_errors' => $consecutiveErrors,
                'health_data' => $healthData,
                'lock_info' => $lockInfo,
            ];
        }

        return [
            'status' => 'ok',
            'seconds_since_success' => $successAge,
            'health_data' => $healthData,
        ];
    }
}
