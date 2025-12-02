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

        if (!empty($params['extra_params'])) {
            parse_str($params['extra_params'], $extraParams);
            $queryParams = array_merge($queryParams, $extraParams);
        }

        $url = $this->baseUrl . '/medias?' . http_build_query($queryParams);

        $response = $this->makeRequest('GET', $url);

        return $response;
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
