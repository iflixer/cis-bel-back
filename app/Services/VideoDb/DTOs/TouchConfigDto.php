<?php

namespace App\Services\VideoDb\DTOs;

use App\Services\VideoDb\Contracts\ProcessingConfigInterface;

class TouchConfigDto implements ProcessingConfigInterface
{
    public $contentType;    // movie, tvseries, anime-tv-series, show-tv-series
    public $imdbId;         // tt1234567
    public $kinopoiskId;    // 123456
    public $vdbId;          // 12345 (direct VideoDB ID)
    public $enrichFlags;    // ['kinopoisk' => true, 'tmdb' => false, ...]
    public $forceImport;

    public static function fromArray(array $data)
    {
        $dto = new self();
        $dto->contentType = $data['content_type'] ?? null;
        $dto->imdbId = $data['imdb_id'] ?? null;
        $dto->kinopoiskId = $data['kinopoisk_id'] ?? null;
        $dto->vdbId = $data['vdb_id'] ?? null;
        $dto->enrichFlags = $data['enrichments'] ?? [];
        $dto->forceImport = $data['force_import'] ?? false;

        return $dto;
    }

    public function toArray()
    {
        return [
            'content_type' => $this->contentType,
            'imdb_id' => $this->imdbId,
            'kinopoisk_id' => $this->kinopoiskId,
            'vdb_id' => $this->vdbId,
            'enrichments' => $this->enrichFlags,
            'force_import' => $this->forceImport,
        ];
    }

    public function validate()
    {
        $errors = [];

        $validContentTypes = ['movie', 'tvseries', 'anime-tv-series', 'show-tv-series'];
        if (empty($this->contentType)) {
            $errors[] = 'content_type is required';
        } elseif (!in_array($this->contentType, $validContentTypes)) {
            $errors[] = 'content_type must be one of: ' . implode(', ', $validContentTypes);
        }

        $hasIdentifier = !empty($this->imdbId) || !empty($this->kinopoiskId) || !empty($this->vdbId);
        if (!$hasIdentifier) {
            $errors[] = 'One of imdb_id, kinopoisk_id, or vdb_id is required';
        }

        $identifierCount = (!empty($this->imdbId) ? 1 : 0)
            + (!empty($this->kinopoiskId) ? 1 : 0)
            + (!empty($this->vdbId) ? 1 : 0);
        if ($identifierCount > 1) {
            $errors[] = 'Only one identifier (imdb_id, kinopoisk_id, or vdb_id) should be provided';
        }

        return $errors;
    }

    public function isValid()
    {
        return empty($this->validate());
    }

    public function getForceImport()
    {
        return (bool) $this->forceImport;
    }

    public function getEnrichFlags()
    {
        return $this->enrichFlags ?: [];
    }
}
