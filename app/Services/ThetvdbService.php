<?php

// Thetvdb требует отдельно первого запроса с логином для получения jwt потом все запросы делаются с jwt
// Весь класс нужен только чтобы получить thetvdb_id. с которым потом fanart.tv может отдать бекдропы

namespace App\Services;

use App\Video;
use DB;
class ThetvdbService
{

    private $jwt;

    public function __construct()
	{
        $curl = curl_init();
        $apikey = config('thetvdb.token');

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api4.thetvdb.com/v4/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "apikey": "'.$apikey.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Apidog/1.0.0 (https://apidog.com)',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            $response = json_decode($response);
            $this->jwt = $response->data->token;
        }
        //dd($this->jwt);
	}
    public function parseThetvdbByImdbId($imdb_id):string
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api4.thetvdb.com/v4/search/remoteid/'.$imdb_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $this->jwt
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        $thetvdb_id = $response->data[0]->series->id ?? $response->data[0]->episode->seriesId ?? '';
        return $thetvdb_id;
    }



    public function updateVideoWithThetvdbIdByImdbId(&$video)
    {
        $thetvdb_id = $this->parseThetvdbByImdbId($video->imdb);
        if (!empty($thetvdb_id)) {
            $video->thetvdb = $thetvdb_id;
        }
        return true;
    }

    public function updateMultipleVideosIds($limit)
    {
        $response = [];
        $videos = Video::whereNull('thetvdb')
            ->whereNotNull('imdb')
            ->where('imdb', '!=', '')
            ->where('tupe', '=', 'episode')
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            $response[] = ['id' => $video->id];
            $this->updateVideoWithThetvdbIdByImdbId($video);
        }

        return $response;
    }
}
