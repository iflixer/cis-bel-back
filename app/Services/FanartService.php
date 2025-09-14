<?php

namespace App\Services;

use App\Video;
use App\Country;
use App\Genre;
use App\Actor;
use App\Director;
use App\Link_country;
use App\Link_genre;
use App\Link_actor;
use App\Link_director;
use DB;
class FanartService
{
    public function parseFanartByImdbId($imdb_id)
    {
        $u = "https://webservice.fanart.tv/v3/movies/{$imdb_id}?api_key=2d91143bea175a2959d5a383be23425c";

        $start_time = microtime(true);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $u,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);


        $response = curl_exec($curl);
        curl_close($curl);

        if (!empty($GLOBALS['debug_tmdb_import'])) {
            echo "parseFanartByImdbId API request: $u\n";
            // echo "parseTmdbByImdbId API response: $response\n";
            echo "parseFanartByImdbId API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        $response = json_decode($response);
        $res = [];
		if (!empty($response->moviebackground)) {
			$backdrop =  $response->moviebackground[0];
		} elseif (!empty($response->movie4kbackground)) {
			$backdrop =  $response->movie4kbackground[0];
		} elseif (!empty($response->moviethumb)) {
			$backdrop =  $response->moviethumb[0];
		}

       if (!empty($backdrop) && !empty($backdrop->url)) {
            $res['backdrop'] = $backdrop->url;
        }
        return $res;
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
        $videos = Video::where('update_fanart', '=', 0)
            ->where('imdb', '!=', null)
            ->where('imdb', '!=', '')
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
