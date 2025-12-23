<?php

namespace App\Http\Controllers;

use App\Services\VideoDb\VideoTouchService;
use App\Services\VideoDb\VideoDbSyncService;
use App\Services\VideoDb\VideoDbApiClient;
use App\Services\VideoDb\SyncProgressTracker;
use App\Services\VideoDb\DTOs\TouchConfigDto;
use App\Services\VideoDb\Enrichments\KinopoiskEnrichment;
use App\Services\VideoDb\Enrichments\TmdbEnrichment;
use App\Services\VideoDb\Enrichments\ThetvdbEnrichment;
use App\Services\VideoDb\Enrichments\FanartEnrichment;
use App\Services\VideoDb\Enrichments\OpenaiEnrichment;
use App\Services\VideoDb\Enrichments\KinopoiskDevEnrichment;
use Illuminate\Http\Request;

class VideoTouchController extends Controller
{
    /**
     * GET /videotouch/search
     */
    public function search(Request $request)
    {
        $config = $this->buildConfig($request);

        $apiClient = new VideoDbApiClient();
        $progressTracker = new SyncProgressTracker('touch');
        $syncService = new VideoDbSyncService($apiClient, $progressTracker);
        $touchService = new VideoTouchService($apiClient, $syncService);

        $result = $touchService->searchContent($config);

        return response()->json($result);
    }

    /**
     * POST /videotouch/sync
     */
    public function sync(Request $request)
    {
        $config = $this->buildConfig($request);

        $apiClient = new VideoDbApiClient();
        $progressTracker = new SyncProgressTracker('touch');
        $syncService = new VideoDbSyncService($apiClient, $progressTracker);
        $touchService = new VideoTouchService($apiClient, $syncService);

        $this->registerEnrichments($touchService);

        $result = $touchService->touchVideo($config);

        return response()->json($result);
    }

    protected function buildConfig(Request $request)
    {
        $data = [
            'content_type' => $request->input('content_type'),
            'imdb_id' => $request->input('imdb_id'),
            'kinopoisk_id' => $request->input('kinopoisk_id'),
            'vdb_id' => $request->input('vdb_id'),
            'force_import' => (bool) $request->input('force_import', false),
        ];

        $enrichments = $request->input('enrichments');
        if (is_array($enrichments)) {
            $data['enrichments'] = [
                'kinopoisk' => (bool) ($enrichments['kinopoisk'] ?? false),
                'tmdb' => (bool) ($enrichments['tmdb'] ?? false),
                'thetvdb' => (bool) ($enrichments['thetvdb'] ?? false),
                'fanart' => (bool) ($enrichments['fanart'] ?? false),
                'openai' => (bool) ($enrichments['openai'] ?? false),
                'kinopoiskdev' => (bool) ($enrichments['kinopoiskdev'] ?? false),
            ];
        } else {
            $data['enrichments'] = [
                'kinopoisk' => false,
                'tmdb' => false,
                'thetvdb' => false,
                'fanart' => false,
                'openai' => false,
                'kinopoiskdev' => false,
            ];
        }

        return TouchConfigDto::fromArray($data);
    }

    protected function registerEnrichments(VideoTouchService $touchService)
    {
        $touchService->registerEnrichment('kinopoisk', new KinopoiskEnrichment());
        $touchService->registerEnrichment('tmdb', new TmdbEnrichment());
        $touchService->registerEnrichment('thetvdb', new ThetvdbEnrichment());
        $touchService->registerEnrichment('fanart', new FanartEnrichment());
        $touchService->registerEnrichment('openai', new OpenaiEnrichment());
        $touchService->registerEnrichment('kinopoiskdev', new KinopoiskDevEnrichment());
    }
}
