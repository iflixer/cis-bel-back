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



    public function updateVideoWithTmdbData(&$video)
    {
        if (!$video || !$video->imdb) {
            return false;
        }

        $film = $this->parseTmdbByImdbId($video->imdb);

        // $film->backdrop_path
        // $tmdb->release_date;
        // $imdb_id = $tmdb->imdb_id;
        
        $video->update_tmdb = 1;

        if (empty($film)) {
            return false;
        }

        if (empty($video->img) && !empty($film->poster_path)) $video->img = $film->poster_path;
        if (empty($video->backdrop) && !empty($film->backdrop_path)) $video->backdrop = $film->backdrop_path;
        if (!empty($film->release_date)) $video->year = substr($film->release_date, 0, 4);
        if (!empty($film->first_air_date)) $video->year = substr($film->first_air_date, 0, 4);
        if (!empty($film->vote_average)) $video->tmdb_vote_average = $film->vote_average;
        if (!empty($film->vote_count)) $video->tmdb_vote_count = $film->vote_count;
        if (!empty($film->popularity)) $video->tmdb_popularity = $film->popularity;

        $video->update_tmdb = 2;
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
            $this->updateVideoWithTmdbData($video);
        }

        return $response;
    }
}
