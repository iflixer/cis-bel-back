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
class TmdbService
{
    public function parseTmdbByImdbId($imdb_id)
    {
        $u = "https://api.themoviedb.org/3/find/{$imdb_id}?external_source=imdb_id";

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
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . config('tmdb.token'),
                "accept: application/json"
            ],
        ]);


        $response = curl_exec($curl);
        curl_close($curl);

        if (!empty($GLOBALS['debug_tmdb_import'])) {
            echo "parseTmdbByImdbId API request: $u\n";
            // echo "parseTmdbByImdbId API response: $response\n";
            echo "parseTmdbByImdbId API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        $response = json_decode($response);
        $res = [];
		if (!empty($response->movie_results)) {
			$res =  $response->movie_results[0];
		}
		if (!empty($response->tv_results)) {
			$res = $response->tv_results[0];
		}

       if (!empty($res->backdrop_path)) {
            // "https://image.tmdb.org/t/p/original"
            $res->backdrop_path = "https://image.tmdb.org/t/p/original".$res->backdrop_path;
        }
        if (!empty($res->poster_path)) {
            $res->poster_path = "https://image.tmdb.org/t/p/original".$res->poster_path;
        }
        return $res;
    }



    public function updateVideoWithTmdbData($videoId)
    {
        $video = Video::find($videoId);

        if (!$video || !$video->imdb) {
            return false;
        }

        $film = $this->parseTmdbByImdbId($video->imdb);

        // $film->backdrop_path
        // $tmdb->release_date;
        // $imdb_id = $tmdb->imdb_id;
        
        Video::where('id', $videoId)->update(['update_tmdb' => 1]);

        if (empty($film)) {
            return false;
        }

        $updateData = [];

        if (empty($video->img) && !empty($film->poster_path)) {
            $updateData['img'] = $film->poster_path;
        }
        if (empty($video->backdrop) && !empty($film->backdrop_path)) {
            $updateData['backdrop'] = $film->backdrop_path;
        }
        if (empty($video->year) && !empty($film->release_date)) {
            $updateData['year'] = substr($film->release_date, 0, 4);
        }
        if (empty($video->tmdb_vote_average) && !empty($film->vote_average)) {
            $updateData['tmdb_vote_average'] = $film->vote_average;
        }
        if (empty($video->tmdb_vote_count) && !empty($film->vote_count)) {
            $updateData['tmdb_vote_count'] = $film->vote_count;
        }
        if (empty($video->tmdb_popularity) && !empty($film->popularity)) {
            $updateData['tmdb_popularity'] = $film->popularity;
        }

        if (empty($updateData)) {
            return false;
        }
        $updateData['update_tmdb'] = 2;
        Video::where('id', $videoId)->update($updateData);
        return true;
    }

    public function updateMultipleVideos($limit)
    {
        $response = [];
        $videos = Video::where('update_tmdb', '=', 0)
            ->where('imdb', '!=', null)
            ->where('imdb', '!=', '')
            ->where('backdrop', '=', '')
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            if (!$video->imdb) {
                continue;
            }
            $response[] = ['id' => $video->id];
            $this->updateVideoWithTmdbData($video->id);
        }

        return $response;
    }
}
