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
    private $cdnApiDomain;
    private $cdnhub_img_resizer_domain;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->cdnApiDomain = Seting::where('name', 'cdnhub_api_domain')->first()->value;
        $this->cdnhub_img_resizer_domain = Seting::where('name', 'cdnhub_img_resizer_domain')->first()->value;
    }

    public function actors(): array
    {
        $page = (int) ($this->request->input('page', 1));
        $limit = (int) ($this->request->input('limit', 50));
        $actorService = new ActorService($this->cdnApiDomain, $this->cdnhub_img_resizer_domain);
        return $actorService->getPaginatedActors($page, $limit);
    }

    public function directors(): array
    {
        $page = (int) ($this->request->input('page', 1));
        $limit = (int) ($this->request->input('limit', 50));
        $directorService = new DirectorService($this->cdnApiDomain, $this->cdnhub_img_resizer_domain);
        return $directorService->getPaginatedDirectors($page, $limit);
    }
}
