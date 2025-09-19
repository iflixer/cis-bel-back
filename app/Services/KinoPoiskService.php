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

class KinoPoiskService
{
    public function parseKinoPoisk($id)
    {
        $start_time = microtime(true);
        $u = 'https://kinopoiskapiunofficial.tech/api/v2.2/films/' . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $u);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'X-API-KEY: ' . config('kinopoisk.token')
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if (!empty($GLOBALS['debug_kinopoisk_import'])) {
            echo "parseKinoPoisk API request: $u\n";
            // echo "parseKinoPoisk API response: $response\n";
            echo "parseKinoPoisk API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        return json_decode($response);
    }

    public function parseKinoPoiskStaff($id): array
    {
        $start_time = microtime(true);
        $u = 'https://kinopoiskapiunofficial.tech/api/v1/staff?filmId=' . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $u);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . config('kinopoisk.token')
        ]);

        $response = curl_exec($curl);
        $errno    = curl_errno($curl);
        $errstr   = curl_error($curl);
        $status   = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($errno !== 0 || $response === false) {
            // логируй по желанию: logger()->warning("KP staff cURL error", compact('errno','errstr'));
            if (!empty($GLOBALS['debug_kinopoisk_import'])) {
                echo "parseKinoPoiskStaff API cURL error: $errstr ($errno)\n";
            }
            return [];
        }
        if ($status < 200 || $status >= 300) {
            // logger()->warning("KP staff HTTP $status", ['body' => $response]);
            if (!empty($GLOBALS['debug_kinopoisk_import'])) {
                echo "parseKinoPoiskStaff API HTTP error: $status\n";
            }
            return [];
        }
        $data = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // logger()->warning("KP staff bad JSON", ['error' => json_last_error_msg()]);
            if (!empty($GLOBALS['debug_kinopoisk_import'])) {
                echo "parseKinoPoiskStaff API bad JSON: " . json_last_error_msg() . "\n";
            }
            return [];
        }


        if (!empty($GLOBALS['debug_kinopoisk_import'])) {
            echo "parseKinoPoiskStaff API request: $u\n";
            // echo "parseKinoPoiskStaff API response: $response\n";
            echo "parseKinoPoiskStaff API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        return $data;
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

    protected function parseStaff(array $staff, int $videoId)
    {
        if (!count($staff)) {
            return;
        }

        Link_actor::where('id_video', $videoId)->delete();
        Link_director::where('id_video', $videoId)->delete();

        foreach ($staff as $staffMember) {
            $professionKey = $staffMember->professionKey ?? 'UNKNOWN';

            switch ($professionKey) {
                case 'ACTOR':
                    $this->storeActor($staffMember, $videoId);
                    break;

                case 'DIRECTOR':
                    $this->storeDirector($staffMember, $videoId);
                    break;

                case 'PRODUCER':
                case 'WRITER':
                case 'COMPOSER':
                case 'OPERATOR':
                case 'EDITOR':
                    break;
            }
        }
    }

    protected function storeActor($staffMember, $videoId)
    {
        $values = array_filter([
            'name_ru'    => $staffMember->nameRu ?? null,
            'name_en'    => $staffMember->nameEn ?? null,
            'poster_url' => $staffMember->posterUrl ?? null,
        ], function ($v) {
            return !empty($v); // уберёт null и пустые строки
        });
        $actor = Actor::updateOrCreate(
            ['kinopoisk_id' => $staffMember->staffId],
            $values
        );

        Link_actor::firstOrCreate(
            [
                'id_video' => $videoId,
                'id_actor' => $actor->id,
            ],
            [
                'character_name' => $staffMember->description ?? null,
            ]
        );
    }

    protected function storeDirector($staffMember, $videoId)
    {
        $values = array_filter([
            'name_ru'    => $staffMember->nameRu ?? null,
            'name_en'    => $staffMember->nameEn ?? null,
            'poster_url' => $staffMember->posterUrl ?? null,
        ], function ($v) {
            return !empty($v); // уберёт null и пустые строки
        });
        $director = Director::updateOrCreate(
            ['kinopoisk_id' => $staffMember->staffId],
            $values
        );

        Link_director::firstOrCreate(
            [
                'id_video' => $videoId,
                'id_director' => $director->id,
            ]
        );
    }

    public function updateVideoWithKinoPoiskData($videoId, $updateNames = false)
    {
        $video = Video::find($videoId);

        if (!$video || !$video->kinopoisk) {
            echo "Error updateVideoWithKinoPoiskData - kinopoisk empty for video {$videoId}";
            return false;
        }

//        if ($video->kinopoisk >= 2000000) {
//            Video::where('id', $videoId)->update(['update_kino' => 1]);
//            return false;
//        }

        $film = $this->parseKinoPoisk($video->kinopoisk);

        if (!$film || !isset($film->data)) {
            echo "Error updateVideoWithKinoPoiskData - parseKinoPoisk empty for video {$videoId}";
            Video::where('id', $videoId)->update(['update_kino' => 1]);
            return false;
        }

        $kinoPoisk = $film->data;
        $imdb_id = $film->externalId->imdbId ?? '';

        $this->parseDopElements(
            array_map(function ($item) {
                return $item->genre;
            }, $kinoPoisk->genres),
            new Genre,
            new Link_genre,
            'id_genre',
            $videoId
        );

        $this->parseDopElements(
            array_map(function ($item) {
                return $item->country;
            }, $kinoPoisk->countries),
            new Country,
            new Link_country,
            'id_country',
            $videoId
        );

        $staff = $this->parseKinoPoiskStaff($video->kinopoisk);
        $this->parseStaff($staff, $videoId);


        $updateData = [
            'year' => $kinoPoisk->year,
            'description' => $kinoPoisk->description,
            'img' => $kinoPoisk->posterUrl ?? '',
            'update_kino' => 1,
            'film_length' => $kinoPoisk->filmLength,
            'slogan' => $kinoPoisk->slogan,
            'rating_kp' => $kinoPoisk->ratingKinopoisk ?? 0,
            'rating_kp_votes' => $kinoPoisk->ratingKinopoiskVoteCount ?? 0,
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

        if (!empty($imdb_id)) {
            $updateData['imdb'] = $imdb_id; 
        }


        if ($updateNames) {
            $updateData['ru_name'] = $kinoPoisk->nameRu;
            $updateData['name'] = $kinoPoisk->nameEn;
        }

        Video::where('id', $videoId)->update($updateData);

        return true;
    }

    public function updateVideoWithKinoPoiskDataImdbOnly($videoId, $kpId)
    {
        $film = $this->parseKinoPoisk($kpId);
        Video::where('id', $videoId)->update(['update_kino' => 1]);
        if (!$film) {
            return false;
        }
        $imdb_id = $film->externalId->imdbId ?? '';
        if (empty($imdb_id)) {
            return;
        }
        $updateData = [];
        $updateData['imdb'] = $imdb_id;
        $updateData['update_kino'] = 2;
        Video::where('id', $videoId)->update($updateData);
        return true;
    }

    public function updateMultipleVideos($limit)
    {
        $response = [];
        $videos = Video::where('update_kino', null)
            ->whereNotNull('kinopoisk')
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

    public function updateMultipleVideosOnlyImdb($limit)
    {
        $response = [];
        $videos = Video::where('update_kino', null)
            ->whereNotNull('kinopoisk')
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            $response[] = ['id' => $video->id];
            $this->updateVideoWithKinoPoiskDataImdbOnly($video->id, $video->kinopoisk);
        }

        return $response;
    }
}
