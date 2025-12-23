<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\KinopoiskDevService;

class KinopoiskDevEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new KinopoiskDevService();
    }

    public function getName()
    {
        return 'KinopoiskDev';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if (!$this->hasRequiredField($video, 'kinopoisk')) {
            return false;
        }

        if ($forceImport) {
            return true;
        }

        return !$this->isAlreadyEnriched($video, 'update_kinopoisk_dev');
    }

    public function enrich(Video $video)
    {
        $this->service->updateVideoWithKinopoiskDevData($video);
        $video->saveOrFail();

        return $video;
    }
}
