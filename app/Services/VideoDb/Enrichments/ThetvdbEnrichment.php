<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\ThetvdbService;

class ThetvdbEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new ThetvdbService();
    }

    public function getName()
    {
        return 'TheTVDB';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if (!$this->hasRequiredField($video, 'imdb')) {
            return false;
        }

        if ($forceImport) {
            return true;
        }

        return empty($video->thetvdb);
    }

    public function enrich(Video $video)
    {
        return $this->service->updateVideoWithThetvdbIdByImdbId($video);
    }
}
