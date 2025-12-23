<?php

namespace App\Services\VideoDb;

use App\Exceptions\VideoDb\VideoDbApiException;
use App\Seting;

class VideoDbApiClient
{
    protected $baseUrl = 'https://videodb.win/api/v1';
    protected $login;
    protected $password;

    public function __construct()
    {
        $this->login = Seting::where('name', 'loginVDB')->first()->value;
        $this->password = Seting::where('name', 'passVDB')->first()->value;
    }

    public function fetchMedias(array $params)
    {
        $queryParams = [
            'ordering' => $params['ordering'] ?? 'created',
            'limit' => $params['limit'] ?? 100,
            'offset' => $params['offset'] ?? 0,
        ];

        if (!empty($params['vdb_id'])) {
            $queryParams['content_object'] = $params['vdb_id'];
        }

        if (!empty($params['content_type'])) {
            $queryParams['content_type'] = $params['content_type'];
        }

        if (!empty($params['extra_params'])) {
            parse_str($params['extra_params'], $extraParams);
            $queryParams = array_merge($queryParams, $extraParams);
        }

        $url = $this->baseUrl . '/medias?' . http_build_query($queryParams);

        $response = $this->makeRequest('GET', $url);

        return $response;
    }

    public function searchContent($contentType, array $params)
    {
        $endpoint = $this->getEndpointForContentType($contentType);

        $queryParams = [];
        if (!empty($params['imdb_id'])) {
            $queryParams['imdb_id'] = $params['imdb_id'];
        }
        if (!empty($params['kinopoisk_id'])) {
            $queryParams['kinopoisk_id'] = $params['kinopoisk_id'];
        }

        $url = $this->baseUrl . '/' . $endpoint . '/?' . http_build_query($queryParams);

        return $this->makeRequest('GET', $url);
    }

    public function getContentById($contentType, $vdbId)
    {
        $endpoint = $this->getEndpointForContentType($contentType);

        $url = $this->baseUrl . '/' . $endpoint . '/' . $vdbId . '/';

        return $this->makeRequest('GET', $url);
    }

    protected function getEndpointForContentType($contentType)
    {
        $mapping = [
            'movie' => 'movies',
            'tvseries' => 'tv-series',
            'anime-tv-series' => 'anime-tv-series',
            'show-tv-series' => 'show-tv-series',
        ];

        if (!isset($mapping[$contentType])) {
            throw new VideoDbApiException("Unknown content type: {$contentType}");
        }

        return $mapping[$contentType];
    }

    public function fetchSeriesSeasons($contentType, $seriesId)
    {
        $endpointMap = [
            'tvseries' => 'tv-series/seasons',
            'anime-tv-series' => 'anime-tv-series/seasons',
            'show-tv-series' => 'show-tv-series/seasons',
        ];

        if (!isset($endpointMap[$contentType])) {
            throw new VideoDbApiException("Cannot fetch seasons for content type: {$contentType}");
        }

        $endpoint = $endpointMap[$contentType];
        $url = $this->baseUrl . '/' . $endpoint . '/?' . http_build_query(['tv_series' => $seriesId]);

        return $this->makeRequest('GET', $url);
    }

    public function getMediaContentType($contentType)
    {
        $mapping = [
            'movie' => 'movie',
            'tvseries' => 'episode',
            'anime-tv-series' => 'animeepisode',
            'show-tv-series' => 'showepisode',
        ];

        if (!isset($mapping[$contentType])) {
            throw new VideoDbApiException("Unknown content type: {$contentType}");
        }

        return $mapping[$contentType];
    }

    protected function makeRequest($method, $url, $data = null)
    {
        $startTime = microtime(true);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_USERPWD, $this->login . ':' . $this->password);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($data !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new VideoDbApiException("cURL error: {$curlError}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new VideoDbApiException("HTTP {$httpCode}: {$response}");
        }

        $decoded = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new VideoDbApiException("JSON decode error: " . json_last_error_msg());
        }

        return $decoded;
    }
}
