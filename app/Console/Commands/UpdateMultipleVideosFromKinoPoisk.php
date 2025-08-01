<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KinoPoiskService;

class UpdateMultipleVideosFromKinoPoisk extends Command
{

    protected $signature = 'video:update-kinopoisk-batch {limit=10 : Number of videos to update}';
    protected $description = 'Update multiple videos from KinoPoisk API (videos that haven\'t been updated yet)';

    protected KinoPoiskService $kinoPoiskService;

    public function __construct(KinoPoiskService $kinoPoiskService)
    {
        parent::__construct();

        $this->kinoPoiskService = $kinoPoiskService;
    }

    public function handle()
    {
        $limit = (int) $this->argument('limit');

        if ($limit <= 0 || $limit > 100) {
            $this->error('Limit must be between 1 and 100.');
            return 1;
        }

        $this->info("Updating up to {$limit} videos from KinoPoisk...");
        $this->line('');

        $progressBar = $this->output->createProgressBar($limit);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        try {
            $results = $this->kinoPoiskService->updateMultipleVideos($limit);
            
            $progressBar->setProgress(count($results));
            $progressBar->finish();
            
            $this->line('');
            $this->line('');

            if (empty($results)) {
                $this->warn('No videos found to update. All videos with KinoPoisk IDs may already be updated.');
                return 0;
            }

            $this->info("Successfully processed {count($results)} videos!");

            $this->line('');
            $this->info('Processed video IDs:');
            $videoIds = array_column($results, 'id');
            $this->line(implode(', ', $videoIds));
            
            return 0;
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->line('');
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }
}
