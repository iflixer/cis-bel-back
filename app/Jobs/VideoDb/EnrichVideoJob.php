<?php

namespace App\Jobs\VideoDb;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Video;
use App\Services\VideoDb\Enrichments\KinopoiskEnrichment;
use App\Services\VideoDb\Enrichments\TmdbEnrichment;
use App\Services\VideoDb\Enrichments\ThetvdbEnrichment;
use App\Services\VideoDb\Enrichments\FanartEnrichment;
use App\Services\VideoDb\Enrichments\OpenaiEnrichment;
use App\Services\VideoDb\Enrichments\KinopoiskDevEnrichment;

class EnrichVideoJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $videoId;
    protected $enrichmentKey;

    public function __construct($videoId, $enrichmentKey)
    {
        $this->videoId = $videoId;
        $this->enrichmentKey = $enrichmentKey;
    }

    public function handle()
    {
        $video = Video::find($this->videoId);

        if (!$video) {
            echo "Video {$this->videoId} not found, skipping enrichment.\n";
            return;
        }

        try {
            $strategy = $this->getEnrichmentStrategy($this->enrichmentKey);

            if (!$strategy) {
                echo "Unknown enrichment: {$this->enrichmentKey}\n";
                return;
            }

            if ($strategy->shouldEnrich($video, false)) {
                echo "Enriching video {$this->videoId} with {$this->enrichmentKey}...\n";
                $strategy->enrich($video);
                $video->save();
                echo "Video {$this->videoId} enriched successfully.\n";
            } else {
                echo "Video {$this->videoId} already enriched with {$this->enrichmentKey}, skipping.\n";
            }

        } catch (\Exception $e) {
            echo "Enrichment {$this->enrichmentKey} failed for video {$this->videoId}: {$e->getMessage()}\n";

            throw $e;
        }
    }

    protected function getEnrichmentStrategy($key)
    {
        switch ($key) {
            case 'kinopoisk':
                return new KinopoiskEnrichment();
            case 'tmdb':
                return new TmdbEnrichment();
            case 'thetvdb':
                return new ThetvdbEnrichment();
            case 'fanart':
                return new FanartEnrichment();
            case 'openai':
                return new OpenaiEnrichment();
            case 'kinopoiskdev':
                return new KinopoiskDevEnrichment();
            default:
                return null;
        }
    }

    public function failed(\Exception $exception)
    {
        echo "Job failed for video {$this->videoId} ({$this->enrichmentKey}): {$exception->getMessage()}\n";
    }
}
