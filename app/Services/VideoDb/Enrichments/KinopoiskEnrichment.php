<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\KinoPoiskService;

class KinopoiskEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new KinoPoiskService();
    }

    public function getName()
    {
        return 'Kinopoisk';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if (!$this->hasRequiredField($video, 'kinopoisk')) {
            return false;
        }

        if ($forceImport) {
            return true;
        }

        return !$this->isAlreadyEnriched($video, 'update_kino');
    }

    public function enrich(Video $video)
    {
        return $this->service->updateVideoWithKinoPoiskData($video);
    }
}
