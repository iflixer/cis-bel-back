<?php

namespace App\Services\VideoDb\Enrichments;

use App\Services\VideoDb\Contracts\EnrichmentStrategyInterface;
use App\Video;

abstract class AbstractEnrichmentStrategy implements EnrichmentStrategyInterface
{
    protected function hasRequiredField(Video $video, $requiredField)
    {
        return !empty($video->$requiredField);
    }

    protected function isAlreadyEnriched(Video $video, $updateField)
    {
        return !empty($video->$updateField) && $video->$updateField == 2;
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        return true;
    }
}
