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
    public function parseFanartByImdbId($tupe, $ext_id)
    {
        $type = 'tv';
        if ($tupe=='movie') $type = 'movies';
        $u = "https://webservice.fanart.tv/v3/{$type}/{$ext_id}?api_key=" . config('fanart.token');

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

        if ($type == 'movies') {
            if (!empty($response->moviebackground)) {
                $backdrop =  $response->moviebackground[0];
            } elseif (!empty($response->movie4kbackground)) {
                $backdrop =  $response->movie4kbackground[0];
            } elseif (!empty($response->moviethumb)) {
                $backdrop =  $response->moviethumb[0];
            }
        }

        if ($type == 'tv') {
            if (!empty($response->showbackground)) {
                $backdrop =  $response->showbackground[0];
            }
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

        $ext_id = $video->imdb;
        if (!empty($video->thetvdb)) $ext_id = $video->thetvdb;
        $film = $this->parseFanartByImdbId($video->tupe, $ext_id);
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
