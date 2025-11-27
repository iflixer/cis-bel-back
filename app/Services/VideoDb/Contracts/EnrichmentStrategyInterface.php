<?php

namespace App\Services\VideoDb\Contracts;

use App\Video;

interface EnrichmentStrategyInterface
{

    public function shouldEnrich(Video $video, $forceImport = false);

    public function enrich(Video $video);

    public function getName();
}
