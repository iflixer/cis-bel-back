<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActorService;
use App\Services\DirectorService;
use App\Seting;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function actors(): array
    {
        $page = (int) ($this->request->input('page', 1));
        $limit = (int) ($this->request->input('limit', 50));

        $cdnApiDomain = Seting::where('name', 'cdnhub_api_domain')->first()->value;
        $actorService = new ActorService($cdnApiDomain);

        return $actorService->getPaginatedActors($page, $limit);
    }

    public function directors(): array
    {
        $page = (int) ($this->request->input('page', 1));
        $limit = (int) ($this->request->input('limit', 50));

        $cdnApiDomain = Seting::where('name', 'cdnhub_api_domain')->first()->value;
        $directorService = new DirectorService($cdnApiDomain);

        return $directorService->getPaginatedDirectors($page, $limit);
    }
}
