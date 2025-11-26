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
     */
    public function sync(Request $request)
    {
        set_time_limit(0);

        $lockInfo = $this->progressTracker->isLocked();
        if ($lockInfo) {
            echo "ERROR: Sync already in progress!\n";
            echo "Locked since: " . $lockInfo['locked_since'] . " seconds ago\n";
            echo "TTL remaining: " . $lockInfo['ttl_remaining'] . " seconds\n";
            echo "Use /videodb/sync/reset to force unlock if needed.\n";
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

        echo "VideoDB Full Sync Start: limit={$limit} offset={$offset}\n";

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

            $result = $this->syncService->syncMedias($configDto);

            echo "\nSync completed successfully!\n";
            echo "Total processed: {$result['processed']}\n";
            echo "New videos: {$result['new_videos']}\n";
            echo "Updated videos: {$result['updated_videos']}\n";
            echo "Enrichments queued: {$result['enrichments_queued']}\n";
            echo "Errors: {$result['errors']}\n";

        } catch (\Exception $e) {
            echo "\nSync failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
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
