<?php

declare(strict_types=1);

namespace App\Services;

use App\Director;
use App\Link_director;
use App\Helpers\Image;

class DirectorService
{
    private $cdnApiDomain;

    public function __construct(string $cdnApiDomain)
    {
        $this->cdnApiDomain = $cdnApiDomain;
    }

    public function getPaginatedDirectors(int $page = 1, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = min(max(1, $limit), 500);
        $offset = ($page - 1) * $limit;

        $totalCount = Director::count();

        $directors = Director::select('id', 'kinopoisk_id', 'name_ru', 'name_en', 'poster_url')
            ->orderBy('id', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        if ($directors->isEmpty()) {
            return $this->buildPaginatedResponse($page, $limit, $totalCount, []);
        }

        $directorIds = $directors->pluck('id')->toArray();

        $videoLinks = Link_director::select('id_director', 'id_video')
            ->whereIn('id_director', $directorIds)
            ->get();

        $linksByDirector = [];
        foreach ($videoLinks as $link) {
            if (!isset($linksByDirector[$link->id_director])) {
                $linksByDirector[$link->id_director] = [];
            }
            $linksByDirector[$link->id_director][] = $link->id_video;
        }

        $result = [];
        foreach ($directors as $director) {
            $result[] = [
                'id' => $director->id,
                'kinopoisk_id' => $director->kinopoisk_id,
                'name_ru' => $director->name_ru,
                'name_en' => $director->name_en,
                'poster_url' => $this->convertPosterUrl($director->id, $director->poster_url),
                'videos' => isset($linksByDirector[$director->id]) ? $linksByDirector[$director->id] : [],
            ];
        }

        return $this->buildPaginatedResponse($page, $limit, $totalCount, $result);
    }

    private function convertPosterUrl(int $directorId, ?string $posterUrl): ?string
    {
        if (empty($posterUrl)) {
            return null;
        }

        return Image::makeInternalImageURL(
            $this->cdnApiDomain,
            'directors',
            $directorId,
            $posterUrl
        );
    }

    private function buildPaginatedResponse(int $page, int $limit, int $totalCount, array $result): array
    {
        $totalPages = (int) ceil($totalCount / $limit);

        $response = [
            'request' => [
                'page' => $page,
                'limit' => $limit,
            ],
            'prev' => null,
            'next' => null,
            'result' => $result,
        ];

        if ($page > 1) {
            $response['prev'] = [
                'page' => $page - 1,
                'limit' => $limit,
            ];
        }

        if ($page < $totalPages) {
            $response['next'] = [
                'page' => $page + 1,
                'limit' => $limit,
            ];
        }

        return $response;
    }
}
