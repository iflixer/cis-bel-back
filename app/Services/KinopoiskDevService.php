<?php

namespace App\Services;

use App\Video;
use App\Seting;

class KinopoiskDevService
{
    protected $token;

    public function __construct()
    {
        $this->token = $this->getToken();
    }

    protected function getToken()
    {
        $setting = Seting::where('name', 'kinopoisk_dev_token')->first();
        return $setting ? $setting->value : null;
    }

    public function fetchMovie($kinopoiskId)
    {
        if (!$this->token) {
            if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
                echo "KinopoiskDevService: No API token configured\n";
            }
            return null;
        }

        $start_time = microtime(true);
        $url = 'https://api.kinopoisk.dev/v1.4/movie/' . $kinopoiskId;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . $this->token,
            'Accept: application/json'
        ]);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $errstr = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
            echo "KinopoiskDevService API request: $url\n";
            echo "KinopoiskDevService API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        if ($errno !== 0 || $response === false) {
            if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
                echo "KinopoiskDevService cURL error: $errstr ($errno)\n";
            }
            return null;
        }

        if ($status < 200 || $status >= 300) {
            if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
                echo "KinopoiskDevService HTTP error: $status\n";
            }
            return null;
        }

        $data = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
                echo "KinopoiskDevService JSON error: " . json_last_error_msg() . "\n";
            }
            return null;
        }

        return $data;
    }

    public function updateVideoWithKinopoiskDevData(Video $video)
    {
        $video->update_kinopoisk_dev = 1;

        if (!$video || !$video->kinopoisk) {
            return false;
        }

        $movie = $this->fetchMovie($video->kinopoisk);

        if (empty($movie) || empty($movie->id)) {
            if (!empty($GLOBALS['debug_kinopoisk_dev_import'])) {
                echo "KinopoiskDev: Movie not found for vid {$video->id} kpid: {$video->kinopoisk}\n";
            }
            return false;
        }

        // Only sync alternativeName
        if (!empty($movie->alternativeName)) {
            $video->alternative_name = $movie->alternativeName;
        }

        $video->update_kinopoisk_dev = 2;
        return true;
    }
}
