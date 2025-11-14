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

    // protected function parseDopElements($elements, $tableElement, $tableLink, $nameColumn, $id)
    // {
    //     $tableLink::where('id_video', $id)->delete();

    //     foreach ($elements as $element) {
    //         if ($element != '') {
    //             $dataTable = $tableElement::where('name', $element)->first();
    //             if (!isset($dataTable->name)) {
    //                 $lastIdTable = $tableElement::create([
    //                     'name' => $element
    //                 ])->id;
    //             } else {
    //                 $lastIdTable = $dataTable->id;
    //             }

    //             $dataLinkTable = $tableLink::where('id_video', $id)->where($nameColumn, $lastIdTable)->get();

    //             if ($dataLinkTable->isEmpty()) {
    //                 $tableLink::create(['id_video' => $id, $nameColumn => $lastIdTable]);
    //             }
    //         }
    //     }
    // }

    protected function parseStaff(array $staff, int $videoId)
    {
        if (!count($staff)) {
            return;
        }

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

        Link_actor::updateOrCreate(
            [
                'id_actor' => $actor->id,
                'id_video' => $videoId
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

        Link_director::updateOrCreate(
            [
                'id_video' => $videoId,
                'id_director' => $director->id,
            ]
        );
    }

    public function updateVideoWithKinoPoiskData(&$video)
    {
        $video->update_kino=1;

        if (!$video || !$video->kinopoisk) {
            return false;
        }

        $kinoPoisk = $this->parseKinoPoisk($video->kinopoisk);
        if (empty($kinoPoisk) || empty($kinoPoisk->kinopoiskId)) {
            echo "kinoPoisk not found for vid {$video->id} kpid: {$video->kinopoisk}";
            return false;
        }

        if (!empty($kinoPoisk->genres)) {
            $genres_names = array_values($kinoPoisk->genres);
            $is_cartoon = false;
            foreach($kinoPoisk->genres as $kp_genre) {
                $genre_name = $kp_genre->genre ?? '';
                if (!empty($genre_name)) {
                    $genre = Genre::updateOrCreate(
                        ['name' => $genre_name]
                    );
                    Link_genre::updateOrCreate(
                        ['id_genre' => $genre->id, 'id_video' => $video->id]
                    );

                    if ($genre_name === 'мультфильм') {
                        $is_cartoon = true;
                    }
                }
            }

            if ($is_cartoon) {
                $video->tupe = 'cartoon';
            }
        }

        if (!empty($kinoPoisk->countries)) {
            foreach($kinoPoisk->countries as $kp_country) {
                $country_name = $kp_country->country ?? '';
                if (!empty($country_name)) {
                    $country = Country::updateOrCreate(
                        ['name' => (string)$country_name]
                    );
                    Link_country::updateOrCreate(
                        ['id_country' => $country->id, 'id_video' => $video->id]
                    );
                }
            }
        }

        // $this->parseDopElements(
        //     array_map(function ($item) {
        //         return $item->genre;
        //     }, $kinoPoisk->genres),
        //     new Genre,
        //     new Link_genre,
        //     'id_genre',
        //     $video->id
        // );

        // $this->parseDopElements(
        //     array_map(function ($item) {
        //         return $item->country;
        //     }, $kinoPoisk->countries),
        //     new Country,
        //     new Link_country,
        //     'id_country',
        //     $video->id
        // );

        $staff = $this->parseKinoPoiskStaff($video->kinopoisk);
        $this->parseStaff($staff, $video->id);

        if (!empty($film->externalId->imdbId)) $video->imdb = $kinoPoisk->imdbId; 
        if (!empty($kinoPoisk->year)) $video->year = $kinoPoisk->year;
        if (!empty($kinoPoisk->description)) $video->description = $kinoPoisk->description;
        if (!empty($kinoPoisk->posterUrl) && !str_contains($kinoPoisk->posterUrl, 'no-poster')) $video->img = $kinoPoisk->posterUrl;
        if (!empty($kinoPoisk->filmLength)) $video->film_length = $this->convertMinsToMovieLength($kinoPoisk->filmLength);
        if (!empty($kinoPoisk->ratingKinopoisk)) $video->rating_kp = $kinoPoisk->ratingKinopoisk;
        if (!empty($kinoPoisk->ratingKinopoiskVoteCount)) $video->rating_kp_votes = $kinoPoisk->ratingKinopoiskVoteCount;
        if (!empty($kinoPoisk->ratingMpaa)) $video->rating_mpaa = $kinoPoisk->ratingMpaa;
        if (!empty($kinoPoisk->ratingAgeLimits)) $video->rating_age_limits = str_replace('age', '', $kinoPoisk->ratingAgeLimits);
        if (!empty($kinoPoisk->premiereRu)) $video->premiere_ru = $kinoPoisk->premiereRu;
        if (!empty($kinoPoisk->distributors)) $video->distributors = $kinoPoisk->distributors;
        if (!empty($kinoPoisk->premiereWorld)) $video->premiere_world = $kinoPoisk->premiereWorld;
        if (!empty($kinoPoisk->premiereDigital)) $video->premiere_digital = $kinoPoisk->premiereDigital;
        if (!empty($kinoPoisk->premiereWorldCountry)) $video->premiere_world_country = $kinoPoisk->premiereWorldCountry;
        if (!empty($kinoPoisk->premiereDvd)) $video->premiere_dvd = $kinoPoisk->premiereDvd;
        if (!empty($kinoPoisk->premiereBluRay)) $video->premiere_blu_ray = $kinoPoisk->premiereBluRay;
        if (!empty($kinoPoisk->distributorRelease)) $video->distributor_release = $kinoPoisk->distributorRelease;
        if (!empty($kinoPoisk->facts)) $video->facts = json_encode($kinoPoisk->facts);
        if (!empty($kinoPoisk->seasons)) $video->seasons = json_encode($kinoPoisk->seasons);
        if (!empty($kinoPoisk->nameRu)) $video->ru_name = $kinoPoisk->nameRu;
        if (!empty($kinoPoisk->nameEn)) $video->name = $kinoPoisk->nameEn;
        if (!empty($kinoPoisk->nameOriginal)) $video->name = $kinoPoisk->nameOriginal;

        $video->update_kino=2;
        return true;
    }

    protected function convertMinsToMovieLength($minutes): string {
        $hours = intdiv($minutes, 60);      // целые часы
        $mins  = $minutes % 60;             // остаток минут
        return sprintf("%02d:%02d", $hours, $mins);
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
        $videos = Video::where('update_kino', '=', 0)
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
