<?php

declare(strict_types=1);

namespace App\Services;

use App\Video;
use App\File;
use App\Link_genre;
use App\Link_country;
use App\Link_actor;
use App\Link_director;
use App\Screenshot;
use App\Helpers\Image;
use DB;

class VideoSearchService
{
    private $cdnApiDomain;
    private $cdnPlayerDomain;

    public function __construct(string $cdnApiDomain, string $cdnPlayerDomain)
    {
        $this->cdnApiDomain = $cdnApiDomain;
        $this->cdnPlayerDomain = $cdnPlayerDomain;
    }

    public function search(array $searchParams): array
    {
        $params = $this->validateSearchParams($searchParams);
        $query = $this->buildVideoQuery($params);
        $count = $query->count();

        $query->leftJoin('files', 'videos.id', '=', 'files.id_parent')->groupBy('videos.id');
        $videos = $query->offset($params['offset'])->limit($params['limit'])->get()->toArray();

        $enrichedVideos = $this->enrichVideosWithRelatedData($videos);

        $pagination = $this->buildPaginationMetadata(
            $params,
            $count,
            $searchParams
        );

        return [
            'request' => $this->buildRequestMetadata($params),
            'prev' => $pagination['prev'],
            'next' => $pagination['next'],
            'result' => $enrichedVideos,
        ];
    }

    private function validateSearchParams(array $params): array
    {
        $limit_max = 200;

        $kinopoisk_id = $params['kinopoisk_id'] ?? null;
        $imdb_id = $params['imdb_id'] ?? null;
        $title = $params['title'] ?? null;
        $offset = $params['offset'] ?? 0;
        if ($offset < 0) $offset = 0;
        $limit = $params['limit'] ?? 50;
        if ($limit > $limit_max) $limit = $limit_max;
        if ($limit < 0) $limit = $limit_max;

        $orderby = 'id';
        if (isset($params['orderby']) && $params['orderby'] !== null) {
            $orderby = $params['orderby'];
            $allowed_orderby = ['id', 'created_at', 'updated_at'];
            if (!in_array($orderby, $allowed_orderby)) {
                $orderby = 'id';
            }
        }

        $orderby_direction = 'desc';
        if (isset($params['orderby_direction']) && $params['orderby_direction'] !== null) {
            $orderby_direction = $params['orderby_direction'];
            $allowed_orderby_direction = ['desc', 'asc'];
            if (!in_array($orderby_direction, $allowed_orderby_direction)) {
                $orderby_direction = 'desc';
            }
        }

        $type = $params['type'] ?? null;
        $tupe = $this->mapTypeToTupe($type);

        return [
            'kinopoisk_id' => $kinopoisk_id,
            'imdb_id' => $imdb_id,
            'title' => $title,
            'offset' => $offset,
            'limit' => $limit,
            'limit_max' => $limit_max,
            'orderby' => $orderby,
            'orderby_direction' => $orderby_direction,
            'type' => $type,
            'tupe' => $tupe,
        ];
    }

    /**
     * Builds the main video query with filters
     */
    private function buildVideoQuery(array $params)
    {
        $videos = Video::select(
            'videos.id',
            'videos.created_at',
            'videos.updated_at',
            'videos.tupe as type',
            'videos.name as title_orig',
            'videos.ru_name as title_rus',
            'videos.quality',
            'videos.year',
            'videos.kinopoisk as kinopoisk_id',
            'videos.imdb as imdb_id',
            'videos.description',
            'videos.img as poster',
            'videos.backdrop',
            'videos.tmdb_popularity',
            'videos.tmdb_vote_average',
            'videos.tmdb_vote_count',
            'videos.film_length as duration',
            'videos.slogan',
            'videos.rating_kp',
            'videos.rating_kp_votes',
            'videos.rating_age_limits as age'
        );

        if ($params['kinopoisk_id']) {
            $kinopoisk_ids = explode(',', $params['kinopoisk_id']);
            $videos->whereIn('videos.kinopoisk', $kinopoisk_ids);
        } else {
            if ($params['imdb_id']) {
                $imdb_ids = explode(',', $params['imdb_id']);
                $videos->whereIn('videos.imdb', $imdb_ids);
            } else {
                if ($params['title']) {
                    $videos->where('videos.ru_name', 'like', "%{$params['title']}%")
                        ->orWhere('videos.name', 'like', "%{$params['title']}%")
                        ->orderBy('videos.' . $params['orderby'], $params['orderby_direction']);
                } else {
                    $videos->orderBy('videos.' . $params['orderby'], $params['orderby_direction']);
                }
            }
        }

        if (!empty($params['tupe'])) {
            $videos->where('videos.tupe', $params['tupe']);
        }

        return $videos;
    }

    private function enrichVideosWithRelatedData(array $videos): array
    {
        if (empty($videos)) {
            return $videos;
        }

        $videoIds = array_column($videos, 'id');
        $genresData = $this->batchLoadGenres($videoIds);
        $countriesData = $this->batchLoadCountries($videoIds);
        $actorsData = $this->batchLoadActors($videoIds);
        $directorsData = $this->batchLoadDirectors($videoIds);
        $translationsData = $this->batchLoadTranslations($videoIds);
        $fileIds = [];
        foreach ($translationsData as $videoTranslations) {
            foreach ($videoTranslations as $translation) {
                $fileIds[] = $translation['id_file'];
            }
        }

        $screenshotsData = $this->batchLoadScreenshots($fileIds);
        $lastSeasonEpisodeData = $this->batchLoadLastSeasonEpisode($videoIds);

        foreach ($videos as $key => $video) {
            $this->transformVideoData(
                $video,
                $key,
                $videos,
                [
                    'genres' => $genresData,
                    'countries' => $countriesData,
                    'actors' => $actorsData,
                    'directors' => $directorsData,
                    'translations' => $translationsData,
                    'screenshots' => $screenshotsData,
                    'lastSeasonEpisode' => $lastSeasonEpisodeData,
                ]
            );
        }

        return $videos;
    }

    private function batchLoadGenres(array $videoIds): array
    {
        $genresData = [];
        if (!empty($videoIds)) {
            $genresRaw = Link_genre::select('link_genres.id_video', 'genres.name')
                ->whereIn('link_genres.id_video', $videoIds)
                ->join('genres', 'link_genres.id_genre', '=', 'genres.id')
                ->get();
            foreach ($genresRaw as $item) {
                if (is_object($item) && isset($item->id_video)) {
                    $genresData[$item->id_video][] = $item->name;
                }
            }
        }
        return $genresData;
    }

    private function batchLoadCountries(array $videoIds): array
    {
        $countriesData = [];
        if (!empty($videoIds)) {
            $countriesRaw = Link_country::select('link_countries.id_video', 'countries.name')
                ->whereIn('link_countries.id_video', $videoIds)
                ->join('countries', 'link_countries.id_country', '=', 'countries.id')
                ->get();
            foreach ($countriesRaw as $item) {
                if (is_object($item) && isset($item->id_video)) {
                    $countriesData[$item->id_video][] = $item->name;
                }
            }
        }
        return $countriesData;
    }

    private function batchLoadActors(array $videoIds): array
    {
        $actorsData = [];
        if (!empty($videoIds)) {
            $actorsRaw = Link_actor::select('link_actors.id_video', 'actors.id', 'actors.name_ru', 'actors.name_en', 'actors.poster_url', 'link_actors.character_name')
                ->whereIn('link_actors.id_video', $videoIds)
                ->join('actors', 'link_actors.id_actor', '=', 'actors.id')
                ->get();
            foreach ($actorsRaw as $item) {
                if (is_object($item) && isset($item->id_video)) {
                    $actorsData[$item->id_video][] = [
                        'id' => $item->id,
                        'name_ru' => $item->name_ru,
                        'name_en' => $item->name_en,
                        'character_name' => $item->character_name,
                        'poster_url' => $item->poster_url
                    ];
                }
            }
        }
        return $actorsData;
    }

    private function batchLoadDirectors(array $videoIds): array
    {
        $directorsData = [];
        if (!empty($videoIds)) {
            $directorsRaw = Link_director::select('link_directors.id_video', 'directors.id', 'directors.name_ru', 'directors.name_en', 'directors.poster_url')
                ->whereIn('link_directors.id_video', $videoIds)
                ->join('directors', 'link_directors.id_director', '=', 'directors.id')
                ->get();
            foreach ($directorsRaw as $item) {
                if (is_object($item) && isset($item->id_video)) {
                    $directorsData[$item->id_video][] = [
                        'id' => $item->id,
                        'name_ru' => $item->name_ru,
                        'name_en' => $item->name_en,
                        'poster_url' => $item->poster_url
                    ];
                }
            }
        }
        return $directorsData;
    }

    private function batchLoadTranslations(array $videoIds): array
    {
        $translationsData = [];
        if (!empty($videoIds)) {
            $translationsRaw = File::select('files.id as id_file', 'files.id_parent', 'translations.id as id', 'translations.title as title', 'translations.tag as tag', 'files.translation_id', 'files.season', 'files.num')
                ->whereIn('files.id_parent', $videoIds)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->get();

            foreach ($translationsRaw as $item) {
                if (is_object($item) && isset($item->id_parent) && isset($item->translation_id)) {
                    $key = $item->id_parent . '_' . $item->translation_id;
                    if (!isset($translationsData[$item->id_parent][$key])) {
                        $translationsData[$item->id_parent][$key] = [
                            'id_file' => $item->id_file,
                            'id' => $item->id,
                            'title' => $item->title,
                            'tag' => $item->tag,
                            'translation_id' => $item->translation_id,
                            'max_season' => $item->season,
                            'max_episode' => $item->num
                        ];
                    } else {
                        if ($item->season > $translationsData[$item->id_parent][$key]['max_season'] ||
                            ($item->season == $translationsData[$item->id_parent][$key]['max_season'] && $item->num > $translationsData[$item->id_parent][$key]['max_episode'])) {
                            $translationsData[$item->id_parent][$key]['max_season'] = $item->season;
                            $translationsData[$item->id_parent][$key]['max_episode'] = $item->num;
                        }
                    }
                }
            }
        }
        return $translationsData;
    }

    private function batchLoadScreenshots(array $fileIds): array
    {
        $screenshotsData = [];
        if (!empty($fileIds)) {
            $screenshotsRaw = Screenshot::select('id_file', 'url', 'num')
                ->whereIn('id_file', $fileIds)
                ->orderBy('id_file')
                ->orderBy('num')
                ->get();
            foreach ($screenshotsRaw as $item) {
                if (is_object($item) && isset($item->id_file)) {
                    $screenshotsData[$item->id_file][] = $item->url;
                }
            }
        }
        return $screenshotsData;
    }

    private function batchLoadLastSeasonEpisode(array $videoIds): array
    {
        $lastSeasonEpisodeData = [];
        if (!empty($videoIds)) {
            $lastSeasonEpisodeRaw = DB::table('files')
                ->select('id_parent', DB::raw('MAX(season) as season'), DB::raw('MAX(num) as episode'))
                ->whereIn('id_parent', $videoIds)
                ->groupBy('id_parent')
                ->get();
            foreach ($lastSeasonEpisodeRaw as $item) {
                if (is_object($item) && isset($item->id_parent)) {
                    $lastSeasonEpisodeData[$item->id_parent] = [
                        'season' => $item->season,
                        'episode' => $item->episode
                    ];
                }
            }
        }
        return $lastSeasonEpisodeData;
    }

    private function transformVideoData(array &$video, int $key, array &$videos, array $relatedData): void
    {
        $contentType = $this->setContentType($video['type']);
        $is_serial = $contentType['is_serial'];
        $videos[$key]['type'] = $contentType['type'];

        $videos[$key]['quality'] = explode(' ', $video['quality'])[0];
        $videos[$key]['iframe_url'] = "https://cdn0.{$this->cdnPlayerDomain}/show/{$video['id']}";
        $videos[$key]['poster'] = $this->makeInternalImageURL('videos', $video['id'], $video['poster']);
        $videos[$key]['backdrop'] = $this->makeInternalImageURL('videos', $video['id'], $video['backdrop']);

        if (isset($relatedData['genres'][$video['id']])) {
            $videos[$key]['genres'] = $relatedData['genres'][$video['id']];
        }

        if (isset($relatedData['countries'][$video['id']])) {
            $videos[$key]['countries'] = $relatedData['countries'][$video['id']];
        }

        if (isset($relatedData['actors'][$video['id']])) {
            foreach ($relatedData['actors'][$video['id']] as $actor) {
                $videos[$key]['actors'][] = [
                    'name_ru' => $actor['name_ru'],
                    'name_en' => $actor['name_en'],
                    'character_name' => $actor['character_name'],
                    'poster_url' => $this->makeInternalImageURL('actors', $actor['id'], $actor['poster_url'])
                ];
            }
        }

        if (isset($relatedData['directors'][$video['id']])) {
            foreach ($relatedData['directors'][$video['id']] as $director) {
                $videos[$key]['directors'][] = [
                    'name_ru' => $director['name_ru'],
                    'name_en' => $director['name_en'],
                    'poster_url' => $this->makeInternalImageURL('directors', $director['id'], $director['poster_url'])
                ];
            }
        }

        if (isset($relatedData['translations'][$video['id']])) {
            foreach ($relatedData['translations'][$video['id']] as $translation) {
                $ss = [];
                if (isset($relatedData['screenshots'][$translation['id_file']])) {
                    $ss = array_map(function($url) use ($translation) {
                        return $this->makeInternalImageURL('screenshots', $translation['id_file'], $url);
                    }, $relatedData['screenshots'][$translation['id_file']]);
                }

                $translationItem = [
                    'id' => $translation['id'],
                    'title' => $translation['tag'] ?: $translation['title'],
                    'screens' => $ss
                ];

                if ($is_serial) {
                    $translationItem['season'] = $translation['max_season'];
                    $translationItem['episode'] = $translation['max_episode'];
                }

                $videos[$key]['translations'][] = $translationItem;
            }
        }

        if ($is_serial && isset($relatedData['lastSeasonEpisode'][$video['id']])) {
            $videos[$key]['season'] = $relatedData['lastSeasonEpisode'][$video['id']]['season'];
            $videos[$key]['episode'] = $relatedData['lastSeasonEpisode'][$video['id']]['episode'];
        }
    }

    private function setContentType(string $internalType): array
    {
        $typeMap = [
            'movie' => ['type' => 'movie', 'is_serial' => false],
            'episode' => ['type' => 'serial', 'is_serial' => true],
            'anime' => ['type' => 'anime', 'is_serial' => false],
            'animeepisode' => ['type' => 'animeserial', 'is_serial' => true],
            'showepisode' => ['type' => 'showserial', 'is_serial' => true],
        ];

        return $typeMap[$internalType] ?? ['type' => $internalType, 'is_serial' => false];
    }

    private function makeInternalImageURL(string $type, int $id, ?string $url): string
    {
        return Image::makeInternalImageURL(
            $this->cdnApiDomain,
            $type,
            $id,
            $url
        );
    }

    private function buildPaginationMetadata(array $params, int $count, array $originalParams): array
    {
        $prev = null;
        $next = null;

        if ($params['offset'] > 0) {
            $prev = [
                'offset' => $params['offset'] - $params['limit'],
                'limit' => $params['limit'],
            ];
            if ($originalParams['kinopoisk_id'] ?? null) {
                $prev['field'] = 'kinopoisk_id';
                $prev['value'] = $originalParams['kinopoisk_id'];
            }
            if ($originalParams['imdb_id'] ?? null) {
                $prev['field'] = 'imdb_id';
                $prev['value'] = $originalParams['imdb_id'];
            }
            if ($originalParams['title'] ?? null) {
                $prev['field'] = 'title';
                $prev['value'] = $originalParams['title'];
            }
        }

        if ($count > $params['offset'] && ($params['offset'] + $params['limit']) < $count) {
            $next = [
                'offset' => $params['offset'] + $params['limit'],
                'limit' => $params['limit'],
            ];
            if ($originalParams['kinopoisk_id'] ?? null) {
                $next['field'] = 'kinopoisk_id';
                $next['value'] = $originalParams['kinopoisk_id'];
            }
            if ($originalParams['imdb_id'] ?? null) {
                $next['field'] = 'imdb_id';
                $next['value'] = $originalParams['imdb_id'];
            }
            if ($originalParams['title'] ?? null) {
                $next['field'] = 'title';
                $next['value'] = $originalParams['title'];
            }
        }

        return ['prev' => $prev, 'next' => $next];
    }

    private function buildRequestMetadata(array $params): array
    {
        return [
            'type' => $params['type'],
            'type_help' => 'one of movie,serial,anime,animeserial,showserial',
            'offset' => $params['offset'],
            'limit' => $params['limit'],
            'limit_help' => "<={$params['limit_max']}",
            'orderby' => $params['orderby'],
            'orderby_help' => 'id,created_at,updated_at',
            'orderby_direction' => $params['orderby_direction'],
            'orderby_direction_help' => 'desc,asc',
            'kinopoisk_id' => $params['kinopoisk_id'],
            'imdb_id' => $params['imdb_id'],
            'title' => $params['title'],
        ];
    }

    private function mapTypeToTupe(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        $typeMap = [
            'movie' => 'movie',
            'serial' => 'episode',
            'anime' => 'anime',
            'animeserial' => 'animeepisode',
            'showserial' => 'showepisode',
        ];

        return $typeMap[$type] ?? null;
    }
}
