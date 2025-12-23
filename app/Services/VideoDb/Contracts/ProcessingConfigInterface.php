<?php

namespace App\Services\VideoDb\Contracts;

interface ProcessingConfigInterface
{
    public function getForceImport();
    public function getEnrichFlags();
}
