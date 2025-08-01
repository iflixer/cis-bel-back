<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KinoPoiskService;
use App\Video;

class UpdateVideoFromKinoPoisk extends Command
{

    protected $signature = 'video:update-kinopoisk {video_id : The ID of the video to update} {--update-names : Update video names (ru_name and name)}';
    protected $description = 'Update video fields from KinoPoisk API for a specified video ID';

    protected KinoPoiskService $kinoPoiskService;

    public function __construct(KinoPoiskService $kinoPoiskService)
    {
        parent::__construct();

        $this->kinoPoiskService = $kinoPoiskService;
    }

    public function handle()
    {
        $videoId = $this->argument('video_id');
        $updateNames = $this->option('update-names');

        if (!is_numeric($videoId)) {
            $this->error('Video ID must be a number.');
            return 1;
        }

        $video = Video::find($videoId);
        if (!$video) {
            $this->error("Video with ID {$videoId} not found.");
            return 1;
        }

        if (!$video->kinopoisk) {
            $this->error("Video with ID {$videoId} does not have a KinoPoisk ID.");
            return 1;
        }

        $this->info("Updating video ID: {$videoId}");
        $this->info("Video name: {$video->name}");
        $this->info("KinoPoisk ID: {$video->kinopoisk}");
        
        if ($updateNames) {
            $this->info("Names will be updated from KinoPoisk");
        }

        $this->line('');

        $progressBar = $this->output->createProgressBar(1);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        try {
            $result = $this->kinoPoiskService->updateVideoWithKinoPoiskData($videoId, $updateNames);
            
            $progressBar->advance();
            $progressBar->finish();
            
            $this->line('');
            $this->line('');

            if ($result) {
                $this->info('Video updated successfully!');
                return 0;
            } else {
                $this->error('Failed to update video.');
                return 1;
            }
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->line('');
            $this->line('');
            $this->error('An error occurred: ' . $e->getMessage());

            return 1;
        }
    }
}
