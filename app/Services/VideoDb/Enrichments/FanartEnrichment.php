<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\FanartService;

class FanartEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new FanartService();
    }

    public function getName()
    {
        return 'Fanart.tv';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if (!$this->hasRequiredField($video, 'imdb')) {
            return false;
        }

        if ($forceImport) {
            return true;
        }

        return !$this->isAlreadyEnriched($video, 'update_fanart');
    }

    public function enrich(Video $video)
    {
        return $this->service->updateVideoWithFanartData($video);
    }
}
