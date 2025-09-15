<?php

namespace App\Services;
use Aws\S3\S3Client;

use App\Video;

class R2Service
{
    private $r2client;

    private $bucket;

    public function __construct()
	{
        $this->bucket = 'tpfx-sng';
		$this->r2client = new \AsyncAws\S3\S3Client([
            'version' => 'latest',
            'region'  => 'auto', // для R2 всегда auto
            'endpoint' => 'https://e259c83419213e1913b0a406cb9b1173.r2.cloudflarestorage.com',
            'credentials' => [
                'key'    => '13a526df08c4f484e46a776affc4e61a',
                'secret' => config('r2.token'),
            ],
        ]);
	}

    public function uploadFileToStorage($filename, $url)
    {
        // Открываем поток (скачивается напрямую)
        $stream = fopen($url, 'r');

        // Отправляем в R2
        $result = $this->r2client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $filename,    
            'Body'   => $stream,
            'ACL'    => 'public-read',     
        ]);

        fclose($stream);

        return $result;
    }



    public function updateVideoWithFanartData($videoId)
    {
        $video = Video::find($videoId);

        if (!$video || !$video->imdb) {
            return false;
        }

        $film = $this->parseFanartByImdbId($video->imdb);
        // $film->backdrop

        Video::where('id', $videoId)->update(['update_fanart' => 1]);

        if (empty($film)) {
            return false;
        }

        $updateData = [];

        if (empty($video->backdrop) && !empty($film['backdrop'])) {
            $updateData['backdrop'] = $film['backdrop'];
        }
        if (empty($video->img) && !empty($film['movieposter'])) {
            $updateData['img'] = $film['movieposter'];
        }
        

        if (empty($updateData)) {
            return false;
        }
        $updateData['update_fanart'] = 2;
        Video::where('id', $videoId)->update($updateData);
        return true;
    }

    public function updateMultipleVideos($limit)
    {
        $response = [];
        $videos = Video::where('update_fanart', 0)
            ->whereNotNull('imdb')
            ->where('imdb', '!=', '')
            ->where(function($q) {
                $q->where('img', '=', '')
                ->orWhere('backdrop', '=', '');
            })
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            if (!$video->imdb) {
                continue;
            }
            $response[] = ['id' => $video->id];
            $this->updateVideoWithFanartData($video->id);
        }

        return $response;
    }
}
