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
use Illuminate\Http\Request;

class VideoDbSyncController extends Controller
{
    protected $syncService;
    protected $progressTracker;

    public function __construct()
    {
        $apiClient = new VideoDbApiClient();
        $progressTracker = new SyncProgressTracker();

        $this->syncService = new VideoDbSyncService($apiClient, $progressTracker);

        $this->syncService->registerEnrichment('kinopoisk', new KinopoiskEnrichment());
        $this->syncService->registerEnrichment('tmdb', new TmdbEnrichment());
        $this->syncService->registerEnrichment('thetvdb', new ThetvdbEnrichment());
        $this->syncService->registerEnrichment('fanart', new FanartEnrichment());
        $this->syncService->registerEnrichment('openai', new OpenaiEnrichment());

        $this->progressTracker = $progressTracker;
    }

    /**
     * Main sync endpoint - syncs entire VideoDB database
     * GET /videodb/sync?limit=100&enrich_kp=1&enrich_tmdb=1
     * GET /videodb/sync?resume=1 - resume interrupted sync
     * GET /videodb/sync?force=1 - force new sync (clears resume state)
     */
    public function sync(Request $request)
    {
        $resume = (bool) $request->input('resume', false);
        $force = (bool) $request->input('force', false);
        if ($force) {
            $this->progressTracker->clearResumeState();
        }

        if ($resume) {
            return $this->handleResume($request);
        }

        $lockInfo = $this->progressTracker->isLocked();
        if ($lockInfo) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Sync already in progress',
                'locked_since' => $lockInfo['locked_since'],
                'ttl_remaining' => $lockInfo['ttl_remaining'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $resumeState = $this->progressTracker->getResumeState();
        if ($resumeState && !$force) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'interrupted_sync_exists',
                'message' => 'An interrupted sync exists. Use resume=1 to continue or force=1 to start fresh.',
                'resume_state' => [
                    'processed' => $resumeState['processed'],
                    'next_offset' => $resumeState['next_offset'],
                    'interrupted_at' => $resumeState['interrupted_at'],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $limit = (int) $request->input('limit', 100);
        $offset = (int) $request->input('offset', 0);
        $vdbId = $request->input('vdb_id');
        $extraParams = $request->input('extra_vdb_parameters', '');
        $forceImport = (bool) $request->input('force_import_extra', false);

        $enrichFlags = [
            'kinopoisk' => (bool) $request->input('enrich_kp', false),
            'tmdb' => (bool) $request->input('enrich_tmdb', false),
            'thetvdb' => (bool) $request->input('enrich_thetvdb', false),
            'fanart' => (bool) $request->input('enrich_fanart', false),
            'openai' => (bool) $request->input('enrich_openai', false),
        ];

        $useJobs = (bool) $request->input('use_jobs', false);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'started',
            'message' => 'Sync started in background',
            'resumable' => true,
            'params' => [
                'limit' => $limit,
                'offset' => $offset,
                'vdb_id' => $vdbId,
            ],
            'progress_url' => '/videodb/sync/progress',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        set_time_limit(0);
        ignore_user_abort(true);

        $progressTracker = $this->progressTracker;
        register_shutdown_function(function() use ($progressTracker) {
            if ($progressTracker->hasLock()) {
                $progressTracker->markInterrupted();
                \Log::info('VideoDb sync interrupted - marked for resume');
            }
        });

        try {
            $configDto = SyncConfigDto::fromArray([
                'limit' => $limit,
                'offset' => $offset,
                'vdb_id' => $vdbId,
                'extra_params' => $extraParams,
                'force_import' => $forceImport,
                'enrich_flags' => $enrichFlags,
                'use_jobs' => $useJobs,
            ]);

            $this->syncService->syncMedias($configDto);

        } catch (\Exception $e) {
            \Log::error('VideoDb sync failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function handleResume(Request $request)
    {
        $resumeState = $this->progressTracker->getResumeState();

        if (!$resumeState) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'No interrupted sync to resume',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $lockInfo = $this->progressTracker->isLocked();
        if ($lockInfo) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Sync already in progress',
                'locked_since' => $lockInfo['locked_since'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $config = $resumeState['config'];
        $nextOffset = $resumeState['next_offset'];
        $previouslyProcessed = $resumeState['processed'];

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'resumed',
            'message' => 'Sync resumed from offset ' . $nextOffset,
            'from_offset' => $nextOffset,
            'previously_processed' => $previouslyProcessed,
            'progress_url' => '/videodb/sync/progress',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        set_time_limit(0);
        ignore_user_abort(true);

        $progressTracker = $this->progressTracker;
        register_shutdown_function(function() use ($progressTracker) {
            if ($progressTracker->hasLock()) {
                $progressTracker->markInterrupted();
                \Log::info('VideoDb sync interrupted during resume - marked for resume');
            }
        });

        try {
            $configDto = SyncConfigDto::fromArray([
                'limit' => $config['limit'] ?? 100,
                'offset' => $nextOffset,
                'vdb_id' => $config['vdb_id'] ?? null,
                'extra_params' => $config['extra_params'] ?? '',
                'force_import' => $config['force_import'] ?? false,
                'enrich_flags' => $config['enrich_flags'] ?? [],
                'use_jobs' => $config['use_jobs'] ?? false,
                'is_resume' => true,
                'previously_processed' => $previouslyProcessed,
            ]);

            $this->syncService->syncMedias($configDto);

        } catch (\Exception $e) {
            \Log::error('VideoDb sync resume failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get current sync progress
     * GET /videodb/sync/progress
     */
    public function progress(Request $request)
    {
        $lockInfo = $this->progressTracker->isLocked();
        $progress = $this->progressTracker->getProgress();

        if (!$progress) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'No active sync found',
                'lock_status' => $lockInfo ? 'locked' : 'unlocked',
                'lock_info' => $lockInfo
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        $progress['lock_status'] = $lockInfo ? 'locked' : 'unlocked';
        $progress['lock_info'] = $lockInfo;

        header('Content-Type: application/json');
        echo json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Reset sync lock (emergency use only)
     * GET /videodb/sync/reset
     */
    public function reset(Request $request)
    {
        $lockInfo = $this->progressTracker->isLocked();

        if (!$lockInfo) {
            echo "No active lock found. Nothing to reset.\n";
            return;
        }

        echo "WARNING: Forcing reset of sync lock!\n";
        echo "Previous lock was active for " . $lockInfo['locked_since'] . " seconds\n";

        $this->progressTracker->forceResetLock();

        echo "Lock released successfully.\n";
        echo "You can now start a new sync.\n";
    }
}
