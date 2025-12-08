<?php

declare(strict_types=1);

namespace App\Services;

use App\Video;
use App\File;
use App\Link_genre;
use App\Link_country;
use App\Link_actor;
use App\Link_director;
use App\Helpers\Image;

class VideoUpdateService
{
    private $cdnApiDomain;
    private $cdnPlayerDomain;

    private $cdnhub_img_resizer_domain;

    public function __construct(string $cdnApiDomain, string $cdnPlayerDomain, string $cdnhub_img_resizer_domain)
    {
        $this->cdnApiDomain = $cdnApiDomain;
        $this->cdnPlayerDomain = $cdnPlayerDomain;
        $this->cdnhub_img_resizer_domain = $cdnhub_img_resizer_domain;
    }

    public function getUpdates(): array
    {
        $files = $this->fetchRecentFiles();

        if (empty($files)) {
            return [
                'movies' => [],
                'serials' => []
            ];
        }

        $videoIds = array_unique(array_column($files, 'id_parent'));

        $videosData = $this->batchLoadVideos($videoIds);
        $genresData = $this->batchLoadGenres($videoIds);
        $countriesData = $this->batchLoadCountries($videoIds);
        $actorsData = $this->batchLoadActors($videoIds);
        $directorsData = $this->batchLoadDirectors($videoIds);
        $translationsData = $this->batchLoadTranslations($videoIds);
        $lastEpisodesData = $this->batchLoadLastEpisodes($videoIds);
        $lastEpisodesByTranslationData = $this->batchLoadLastEpisodesByTranslation($videoIds);

        $movies = [];
        $serials = [];

        foreach ($files as $file) {
            $videoId = $file['id_parent'];

            if (!isset($videosData[$videoId])) {
                continue;
            }

            $video = $videosData[$videoId];

            $isSerial = $this->isSerial($video);

            $updateData = $this->buildUpdateData(
                $file,
                $video,
                $isSerial,
                $genresData[$videoId] ?? [],
                $countriesData[$videoId] ?? [],
                $actorsData[$videoId] ?? [],
                $directorsData[$videoId] ?? [],
                $translationsData[$videoId] ?? [],
                $lastEpisodesData[$videoId] ?? null,
                $lastEpisodesByTranslationData[$videoId] ?? []
            );

            if ($isSerial) {
                $serials[] = $updateData;
            } else {
                $movies[] = $updateData;
            }
        }

        return [
            'movies' => $movies,
            'serials' => $serials
        ];
    }

    private function fetchRecentFiles(): array
    {
        $data = File::select(
            'files.id',
            'id_parent',
            'created_at',
            'season',
            'num as episode',
            'translations.id as t_id',
            'translations.title as t_title',
            'translations.tag as t_tag'
        )
            ->join('translations', 'files.translation_id', '=', 'translations.id')
            ->whereRaw("season = 0 AND num = 0")
            ->orderBy('files.id', 'desc')
            ->limit(300)
            ->get()
            ->toArray();

        $serialsData = File::select(
            'files.id',
            'id_parent',
            'created_at',
            'season',
            'num as episode',
            'translations.id as t_id',
            'translations.title as t_title',
            'translations.tag as t_tag'
        )
            ->join('translations', 'files.translation_id', '=', 'translations.id')
            ->whereRaw("season > 0 AND num > 0")
            ->orderBy('files.id', 'desc')
            ->limit(300)
            ->get()
            ->toArray();

        return array_merge($data, $serialsData);
    }

    private function batchLoadVideos(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $videos = Video::select(
            'id',
            'tupe as type',
            'name as title_orig',
            'ru_name as title_rus',
            'quality',
            'year',
            'kinopoisk as kinopoisk_id',
            'imdb as imdb_id',
            'description',
            'img as poster',
            'backdrop',
            'tmdb_popularity',
            'tmdb_vote_average',
            'tmdb_vote_count',
            'film_length as duration',
            'slogan',
            'videos.rating_kp as rating_kp',
            'videos.rating_kp_votes as rating_kp_votes',
            'rating_age_limits as age'
        )
            ->whereIn('id', $videoIds)
            ->get();

        $result = [];
        foreach ($videos as $video) {
            $result[$video->id] = $video->toArray();
        }

        return $result;
    }

    private function batchLoadGenres(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $genresRaw = Link_genre::select('link_genres.id_video', 'genres.name')
            ->whereIn('link_genres.id_video', $videoIds)
            ->join('genres', 'link_genres.id_genre', '=', 'genres.id')
            ->get();

        $result = [];
        foreach ($genresRaw as $item) {
            if (is_object($item) && isset($item->id_video)) {
                $result[$item->id_video][] = $item->name;
            }
        }

        return $result;
    }

    private function batchLoadCountries(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $countriesRaw = Link_country::select('link_countries.id_video', 'countries.name')
            ->whereIn('link_countries.id_video', $videoIds)
            ->join('countries', 'link_countries.id_country', '=', 'countries.id')
            ->get();

        $result = [];
        foreach ($countriesRaw as $item) {
            if (is_object($item) && isset($item->id_video)) {
                $result[$item->id_video][] = $item->name;
            }
        }

        return $result;
    }

    private function batchLoadActors(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $actorsRaw = Link_actor::select(
            'link_actors.id_video',
            'actors.id',
            'actors.name_ru',
            'actors.name_en',
            'actors.poster_url',
            'link_actors.character_name'
        )
            ->whereIn('link_actors.id_video', $videoIds)
            ->join('actors', 'link_actors.id_actor', '=', 'actors.id')
            ->get();

        $result = [];
        foreach ($actorsRaw as $item) {
            if (is_object($item) && isset($item->id_video)) {
                $result[$item->id_video][] = [
                    'id' => $item->id,
                    'name_ru' => $item->name_ru,
                    'name_en' => $item->name_en,
                    'character_name' => $item->character_name,
                    'poster_url' => $item->poster_url
                ];
            }
        }

        return $result;
    }

    private function batchLoadDirectors(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $directorsRaw = Link_director::select(
            'link_directors.id_video',
            'directors.id',
            'directors.name_ru',
            'directors.name_en',
            'directors.poster_url'
        )
            ->whereIn('link_directors.id_video', $videoIds)
            ->join('directors', 'link_directors.id_director', '=', 'directors.id')
            ->get();

        $result = [];
        foreach ($directorsRaw as $item) {
            if (is_object($item) && isset($item->id_video)) {
                $result[$item->id_video][] = [
                    'id' => $item->id,
                    'name_ru' => $item->name_ru,
                    'name_en' => $item->name_en,
                    'poster_url' => $item->poster_url
                ];
            }
        }

        return $result;
    }

    private function batchLoadTranslations(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $translationsRaw = File::select(
            'files.id_parent',
            'translations.id as id',
            'translations.title as title',
            'translations.tag as tag'
        )
            ->whereIn('files.id_parent', $videoIds)
            ->join('translations', 'files.translation_id', '=', 'translations.id')
            ->groupBy('files.id_parent', 'files.translation_id')
            ->get();

        $result = [];
        foreach ($translationsRaw as $item) {
            if (is_object($item) && isset($item->id_parent)) {
                $result[$item->id_parent][] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'tag' => $item->tag
                ];
            }
        }

        return $result;
    }

    private function batchLoadLastEpisodes(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $lastEpisodesRaw = File::select(
            'id_parent',
            \DB::raw('MAX(season) as season'),
            \DB::raw('MAX(num) as episode')
        )
            ->whereIn('id_parent', $videoIds)
            ->groupBy('id_parent')
            ->get();

        $result = [];
        foreach ($lastEpisodesRaw as $item) {
            if (is_object($item) && isset($item->id_parent)) {
                $result[$item->id_parent] = [
                    'season' => $item->season,
                    'episode' => $item->episode
                ];
            }
        }

        return $result;
    }

    private function batchLoadLastEpisodesByTranslation(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $lastEpisodesRaw = File::select(
            'id_parent',
            'translation_id',
            \DB::raw('MAX(season) as season'),
            \DB::raw('MAX(num) as episode')
        )
            ->whereIn('id_parent', $videoIds)
            ->groupBy('id_parent', 'translation_id')
            ->get();

        $result = [];
        foreach ($lastEpisodesRaw as $item) {
            if (is_object($item) && isset($item->id_parent) && isset($item->translation_id)) {
                if (!isset($result[$item->id_parent])) {
                    $result[$item->id_parent] = [];
                }
                $result[$item->id_parent][$item->translation_id] = [
                    'season' => $item->season,
                    'episode' => $item->episode
                ];
            }
        }

        return $result;
    }

    private function isSerial(array $video): bool
    {
        return isset($video['type']) && $video['type'] !== 'movie';
    }

    private function buildUpdateData(
        array $file,
        array $video,
        bool $isSerial,
        array $genres,
        array $countries,
        array $actors,
        array $directors,
        array $translations,
        ?array $lastEpisode,
        array $lastEpisodesByTranslation
    ): array {
        $data = [];

        $data['update_id'] = $file['id'];
        $data['created'] = $file['created_at'];

        $data['translation'] = [
            'id' => $file['t_id'],
            'title' => $file['t_tag'] ?: $file['t_title'],
        ];

        $data['content'] = [];
        $data['content']['id'] = $video['id'] ?: null;

        $contentType = $this->setContentType($video, $isSerial);
        $data['content']['type'] = $contentType;
        $data['type'] = $video['type'];

        if ($isSerial) {
            $data['season'] = $file['season'];
            $data['episode'] = $file['episode'];
        }

        $data['content']['title_orig'] = $video['title_orig'] ?: null;
        $data['content']['title_rus'] = $video['title_rus'] ?: null;
        $data['content']['year'] = $video['year'] ?: null;
        $data['content']['description'] = $video['description'] ?: null;
        $data['content']['poster'] = $this->makeInternalImageURL('videos', $video['id'], $video['poster']) ?: null;
        $data['content']['backdrop'] = $this->makeInternalImageURL('videos', $video['id'], $video['backdrop']) ?: null;
        $data['content']['duration'] = $video['duration'] ?: null;
        $data['content']['tmdb_popularity'] = $video['tmdb_popularity'] ?: null;
        $data['content']['tmdb_vote_average'] = $video['tmdb_vote_average'] ?: null;
        $data['content']['tmdb_vote_count'] = $video['tmdb_vote_count'] ?: null;
        $data['content']['slogan'] = $video['slogan'] ?: null;
        $data['content']['age'] = $video['age'] ?: null;
        $data['content']['kinopoisk_id'] = $video['kinopoisk_id'] ?: null;
        $data['content']['imdb_id'] = $video['imdb_id'] ?: null;
        $data['content']['rating_kp'] = $video['rating_kp'] ?: null;
        $data['content']['rating_kp_votes'] = $video['rating_kp_votes'] ?: null;

        $data['content']['quality'] = explode(' ', $video['quality'])[0];

        $data['content']['iframe_url'] = "https://{$this->cdnPlayerDomain}/show/{$video['id']}";

        if (!empty($genres)) {
            $data['content']['genres'] = $genres;
        }

        if (!empty($countries)) {
            $data['content']['countries'] = $countries;
        }

        if (!empty($actors)) {
            $data['content']['actors'] = [];
            foreach ($actors as $actor) {
                $data['content']['actors'][] = [
                    'name_ru' => $actor['name_ru'],
                    'name_en' => $actor['name_en'],
                    'character_name' => $actor['character_name'],
                    'poster_url' => $this->makeInternalImageURL('actors', $actor['id'], $actor['poster_url'])
                ];
            }
        }

        if (!empty($directors)) {
            $data['content']['directors'] = [];
            foreach ($directors as $director) {
                $data['content']['directors'][] = [
                    'name_ru' => $director['name_ru'],
                    'name_en' => $director['name_en'],
                    'poster_url' => $this->makeInternalImageURL('directors', $director['id'], $director['poster_url'])
                ];
            }
        }

        if (!empty($translations)) {
            $data['content']['translations'] = [];
            foreach ($translations as $translation) {
                $translationData = [
                    'id' => $translation['id'],
                    'title' => $translation['tag'] ?: $translation['title']
                ];

                if ($isSerial && isset($lastEpisodesByTranslation[$translation['id']])) {
                    $translationData['season'] = $lastEpisodesByTranslation[$translation['id']]['season'];
                    $translationData['episode'] = $lastEpisodesByTranslation[$translation['id']]['episode'];
                }

                $data['content']['translations'][] = $translationData;
            }
        }

        if ($isSerial && $lastEpisode) {
            $data['content']['season'] = $lastEpisode['season'];
            $data['content']['episode'] = $lastEpisode['episode'];
        }

        return $data;
    }

    private function setContentType(array $video, bool $isSerial): string
    {
        if ($isSerial) {
            return 'serial';
        }

        return $video['type'] ?? 'movie';
    }

    private function makeInternalImageURL(string $type, int $id, ?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        return Image::makeInternalImageURL(
            $this->cdnhub_img_resizer_domain,
            $type,
            $id,
            $imagePath
        );
    }
}
