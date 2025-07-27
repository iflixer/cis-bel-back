<?php

namespace App\Services;

use App\Video;
use App\Country;
use App\Genre;
use App\Link_country;
use App\Link_genre;

class KinoPoiskService
{
    public function parseKinoPoisk($id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, 'https://kinopoiskapiunofficial.tech/api/v2.1/films/'.$id);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'X-API-KEY: ' . config('kinopoisk.token')
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    protected function parseDopElements($elements, $tableElement, $tableLink, $nameColumn, $id)
    {
        $tableLink::where('id_video', $id)->delete();

        foreach ($elements as $element) {
            if ($element != '') {
                $dataTable = $tableElement::where('name', $element)->first();
                if (!isset($dataTable->name)) {
                    $lastIdTable = $tableElement::create([
                        'name' => $element
                    ])->id;
                } else {
                    $lastIdTable = $dataTable->id;
                }
                
                $dataLinkTable = $tableLink::where('id_video', $id)->where($nameColumn, $lastIdTable)->get();

                if ($dataLinkTable->isEmpty()) {
                    $tableLink::create(['id_video' => $id, $nameColumn => $lastIdTable]);
                }
            }
        }
    }

    public function updateVideoWithKinoPoiskData($videoId, $updateNames = false)
    {
        $video = Video::find($videoId);
        
        if (!$video || !$video->kinopoisk) {
            return false;
        }

//        if ($video->kinopoisk >= 2000000) {
//            Video::where('id', $videoId)->update(['update_kino' => 1]);
//            return false;
//        }

        $film = $this->parseKinoPoisk($video->kinopoisk);
        
        if (!$film || !isset($film->data)) {
            Video::where('id', $videoId)->update(['update_kino' => 1]);
            return false;
        }

        $kinoPoisk = $film->data;

        $this->parseDopElements(
            array_map(function($item) { return $item->genre; }, $kinoPoisk->genres),
            new Genre,
            new Link_genre,
            'id_genre',
            $videoId
        );

        $this->parseDopElements(
            array_map(function($item) { return $item->country; }, $kinoPoisk->countries),
            new Country,
            new Link_country,
            'id_country',
            $videoId
        );

        $updateData = [
            'year' => $kinoPoisk->year,
            'description' => $kinoPoisk->description,
            'img' => $kinoPoisk->posterUrl,
            'update_kino' => 1,
            'film_length' => $kinoPoisk->filmLength,
            'slogan' => $kinoPoisk->slogan,
            'rating_mpaa' => $kinoPoisk->ratingMpaa,
            'rating_age_limits' => $kinoPoisk->ratingAgeLimits,
            'premiere_ru' => $kinoPoisk->premiereRu,
            'distributors' => $kinoPoisk->distributors,
            'premiere_world' => $kinoPoisk->premiereWorld,
            'premiere_digital' => $kinoPoisk->premiereDigital,
            'premiere_world_country' => $kinoPoisk->premiereWorldCountry,
            'premiere_dvd' => $kinoPoisk->premiereDvd,
            'premiere_blu_ray' => $kinoPoisk->premiereBluRay,
            'distributor_release' => $kinoPoisk->distributorRelease,
            'facts' => json_encode($kinoPoisk->facts),
            'seasons' => json_encode($kinoPoisk->seasons),
        ];

        if ($updateNames) {
            $updateData['ru_name'] = $kinoPoisk->nameRu;
            $updateData['name'] = $kinoPoisk->nameEn;
        }

        Video::where('id', $videoId)->update($updateData);

        return true;
    }

    public function updateMultipleVideos($limit)
    {
        $response = [];
        $videos = Video::where('update_kino', null)
            ->where('kinopoisk', '!=', null)
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            if (!$video->kinopoisk) {
                continue;
            }

            $response[] = ['id' => $video->id];
            $this->updateVideoWithKinoPoiskData($video->id);
        }

        return $response;
    }
}
