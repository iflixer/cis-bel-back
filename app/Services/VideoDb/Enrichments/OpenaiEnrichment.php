<?php

namespace App\Services\VideoDb\Enrichments;

use App\Video;
use App\Services\OpenaiService;

class OpenaiEnrichment extends AbstractEnrichmentStrategy
{
    protected $service;

    public function __construct()
    {
        $this->service = new OpenaiService();
    }

    public function getName()
    {
        return 'OpenAI';
    }

    public function shouldEnrich(Video $video, $forceImport = false)
    {
        if ($forceImport) {
            return true;
        }

        if (!empty($video->description)) {
            return false;
        }

        return !$this->isAlreadyEnriched($video, 'update_openai');
    }

    public function enrich(Video $video)
    {
        $this->service->updateVideoWithOpenaiData($video);
        $video->saveOrFail();

        return $video;

    }
}
