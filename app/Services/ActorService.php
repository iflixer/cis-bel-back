<?php

declare(strict_types=1);

namespace App\Services;

use App\Actor;
use App\Link_actor;
use App\Helpers\Image;

class ActorService
{
    private $cdnApiDomain;

    public function __construct(string $cdnApiDomain)
    {
        $this->cdnApiDomain = $cdnApiDomain;
    }

    public function getPaginatedActors(int $page = 1, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = min(max(1, $limit), 500);
        $offset = ($page - 1) * $limit;

        $totalCount = Actor::count();

        $actors = Actor::select('id', 'kinopoisk_id', 'name_ru', 'name_en', 'poster_url')
            ->orderBy('id', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        if ($actors->isEmpty()) {
            return $this->buildPaginatedResponse($page, $limit, $totalCount, []);
        }

        $actorIds = $actors->pluck('id')->toArray();
        $videoLinks = Link_actor::select('id_actor', 'id_video', 'character_name')
            ->whereIn('id_actor', $actorIds)
            ->get();

        $linksByActor = [];
        foreach ($videoLinks as $link) {
            if (!isset($linksByActor[$link->id_actor])) {
                $linksByActor[$link->id_actor] = [];
            }
            $linksByActor[$link->id_actor][] = [
                'id' => $link->id_video,
                'character_name' => $link->character_name ?? null,
            ];
        }

        $result = [];
        foreach ($actors as $actor) {
            $result[] = [
                'id' => $actor->id,
                'kinopoisk_id' => $actor->kinopoisk_id,
                'name_ru' => $actor->name_ru,
                'name_en' => $actor->name_en,
                'poster_url' => $this->convertPosterUrl($actor->id, $actor->poster_url),
                'videos' => isset($linksByActor[$actor->id]) ? $linksByActor[$actor->id] : [],
            ];
        }

        return $this->buildPaginatedResponse($page, $limit, $totalCount, $result);
    }

    private function convertPosterUrl(int $actorId, ?string $posterUrl): ?string
    {
        if (empty($posterUrl)) {
            return null;
        }

        return Image::makeInternalImageURL(
            $this->cdnApiDomain,
            'actors',
            $actorId,
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
