<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\TmdbService;

class TmdbEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new TmdbService();
    }

    public function getName()
    {
        return 'TMDB';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if (!$this->hasRequiredField($video, 'imdb')) {
            return false;
        }

        if ($forceImport) {
            return true;
        }

        return !$this->isAlreadyEnriched($video, 'update_tmdb');
    }

    public function enrich(Video $video)
    {
        $this->service->updateVideoWithTmdbData($video);
        $video->saveOrFail();

        return $video;
    }
}
