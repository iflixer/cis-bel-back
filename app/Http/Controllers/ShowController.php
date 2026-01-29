<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\File;
use App\Video;
use App\Seting;
use App\Ad;
use App\Helpers\Cloudflare;
use App\PlayerPay;
use App\Helpers\Image;

use App\Domain;
use App\Cdn;
use App\CdnVideo;

use Illuminate\Support\Facades\DB;

use App\Show;

use Cookie;
use Illuminate\Support\Arr;

use Symfony\Component\HttpFoundation\StreamedResponse;
use function is_array;

class ShowController extends Controller{

    protected $loginVDB;
    protected $passVDB;
    protected $cdnhub_api_domain;
    protected $cdnhub_img_resizer_domain;

    protected $cloudflare_captcha_secret;
    protected $cloudflare_captcha_public;
    protected $tg_share_domain;
    protected $cdn_domain;
    protected $cdnhub_player_domain;
    protected $cdnhub_public_domain;
    protected $player_stat_url;


    public function __construct(Request $request){
        $this->request = $request;
        // $this->loginVDB = config('videodb.login');
        // $this->passVDB = config('videodb.password');
        // $this->keyWin = config('videodb.key_win');
        $this->loginVDB = Seting::where('name', 'loginVDB')->value('value') ?? '';
        $this->passVDB = Seting::where('name', 'passVDB')->value('value') ?? '';
        $this->keyWin = Seting::where('name', 'keyWin')->value('value') ?? '';
        $this->cdnhub_api_domain = Seting::where('name', 'cdnhub_api_domain')->value('value') ?? '';
        $this->cdnhub_img_resizer_domain = Seting::where('name', 'cdnhub_img_resizer_domain')->value('value') ?? '';
        $this->cloudflare_captcha_public = Seting::where('name', 'cloudflare_captcha_public')->value('value') ?? '';
        $this->cloudflare_captcha_secret = Seting::where('name', 'cloudflare_captcha_secret')->value('value') ?? '';
        $this->tg_share_domain = Seting::where('name', 'tg_share_domain')->value('value') ?? '';
        $this->cdnhub_player_domain = Seting::where('name', 'cdnhub_player_domain')->value('value') ?? '';
        $this->cdnhub_public_domain = Seting::where('name', 'cdnhub_public_domain')->value('value') ?? '';
        $this->player_stat_url = Seting::where('name', 'player_stat_url')->value('value') ?? '';
        $this->cdn_domain = Seting::where('name', 'cdn_domain')->value('value') ?? '';
    }

    public function player($type = null, $id = 0)
    {
        $start_time = microtime(true);

        $data = [];

        $data['version'] = '1.0.2';
        $data['cloudflare_captcha_public'] = $this->cloudflare_captcha_public;
        $data['player_stat_url'] = $this->player_stat_url;

        $data['unapproved_domain'] = $this->request->domain_approved ? 'false' : 'true';

        if ($type && $id) {
            $video = Video::where($type, $id)->first();
            if (!$video) {
                header("X-CDNHub-error: Video not found");
                header("X-CDNHub-type: ".$type);
                header("X-CDNHub-id: ".$id);
                abort(404);
            }
            $id = $video->id;
        } else {
            if (!$id) {
                $id = $type;
            }
            $video = Video::where('id', $id)->first();
        }

        if (!$video) {
            abort(404);
        }

        if ($video->blacklisted) {
            abort(404);
        }

        if ($video->lock) {
            if ($video->lock == 'FULL') {
                abort(423);
            }
            $lock = $video->lock;
            $lock = str_replace('SNG', 'AZ,AM,BY,KZ,KG,MD,TJ,UZ,TM', $lock);
            $lock = array_unique(
                array_filter(
                    array_map('trim', explode(',', $lock))
                )
            );
            //$ipSource = @file_get_contents('http://ipinfo.io/'.$_SERVER['HTTP_X_FORWARDED_FOR'].'?token=81e9b5a1120863');
            $user_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
            if ($user_country && in_array($user_country, $lock)) {
                abort(423);
            }
        }

        $video->toArray();

        $data['video'] = $video;

        $data['cover_url'] = Image::makeInternalImageURL($this->cdnhub_img_resizer_domain, 'videos', $video->id, $video->backdrop);

        // tgc
        // if ($this->request->input('tgc'))
        //     $tgc = $this->request->input('tgc');
        // else
        //     $tgc = null;
        // if ($tgc)
        //     $this->request->domain = "@{$tgc}";

        // $data['tgc'] = $tgc;

        $data['id'] = $video['id'];

        // force use cdn
        $data['force_cdn'] = null;
        if ($this->request->input('cdn') && intval($this->request->input('cdn')))
            $data['force_cdn'] = intval($this->request->input('cdn'));

        // if (isset($_GET['debug']) && $_GET['debug']) {
        //     var_dump($data['force_cdn']);
        //     var_dump($this->request->all());
        //     die();
        // }

        // video type
        $data['type'] = 'serial';
        if ($video['tupe'] == 'movie' || $video['tupe'] == 'anime' || $video['tupe'] == 'cartoon')
            $data['type'] = 'movie';

        // input autoplay
        $autoplay = false;
        if ($this->request->input('autoplay') && intval($this->request->input('autoplay')))
            $autoplay = true;
            
        $data['autoplay'] = 0;
        if ($autoplay)
            $data['autoplay'] = 1;

        // input start
        $start = 0;
        if ($this->request->input('start') && intval($this->request->input('start')))
            $start = intval($this->request->input('start'));

        $data['start'] = $start;

        // input translate
        $translate = null;
        $data['translate_was_set_by_user'] = false;
        if ($this->request->input('translation') && intval($this->request->input('translation'))) {
            $translate = intval($this->request->input('translation'));
            $data['translate_was_set_by_user'] = true;
        }

        // input season
        $season = null;
        if ($this->request->input('season') && intval($this->request->input('season')))
            $season = intval($this->request->input('season'));

        // input episode
        $episode = null;
        if ($this->request->input('episode') && intval($this->request->input('episode')))
            $episode = intval($this->request->input('episode'));


        $error = $this->inject_media($data,  $translate, $season, $episode);
        if ($error) {
            header("X-CDNHub-error: ".$error);
            $query = Arr::except($this->request->query(), ['season', 'episode']);
            $target = $this->request->url() . (empty($query) ? '' : ('?' . http_build_query($query)));
            return redirect()->to($target, 301);
        }
        $this->inject_translations($data);
        $this->inject_files($data);
        $this->inject_ads($data);

        $domain = Domain::where('name', $this->request->domain)->first();

        $data['domain'] = '';
        if (!empty($domain)) {
            $data['domain'] = $domain->name;
        }

        // update stat for domain
        $this->do_stat($domain);

        // get view for player
        $player_view = 'player';
        if ($domain && $domain->player_view) {
            $player_view = $domain->player_view;
        } else {
            $player_view_global = Seting::where('name', 'player_view')->first()->toArray()['value'];
            if ($player_view_global) {
                $player_view = $player_view_global;
            }
        }

        if ($player_view_arg = $this->request->input('view') ?? '') {
            $player_view = $player_view_arg;
        }

        if (!empty($_REQUEST['debuggy']) && $_REQUEST['debuggy']) {
            dd(DB::getQueryLog());
        }

        // $ref = $this->request->header('referer');
        // $ref_host = $ref ? parse_url($ref, PHP_URL_HOST) : null;
        // $domain_name = $ref_host ?? $this->request->input('domain');

        // $domain = Domain::get_main_info($domain_name);
        // PlayerPay::save_event('load', $domain, $data['media']['id'] ?? 0);
        // Debug::dump_queries(0);
        // die();

        if (!empty($_REQUEST['debug_data_back']) && $_REQUEST['debug_data_back'] == 1) {
            echo '<pre>';
            $d = json_encode($data);
            var_dump(json_decode($d, true));
            die();
        }
        

        header("X-Player-Build-Duration: " . (microtime(true) - $start_time));
        return view($player_view, $data);
    }

    private function inject_media(array &$data, $translate, $season, $episode): string {
        $video = $data['video'];
        $id = $video['id'];
        $data['files'] = [];
        $data['translate'] = $translate;
        $data['translateTitle'] = '';
        $data['season'] = $season;
        $data['episode'] = $episode;
        $data['media'] = [];
        if (in_array($video['tupe'], ['movie', 'anime', 'cartoon'])) {
            $data['is_serial'] = false;
            
            // files

            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();

            if (empty($files)) return "";

            $data['files'] = $files;

            // найти самый наполненный перевод
            $translations_stat = [];
            foreach ($files as $file) {
                $tid = $file['translation_id'];
                $translations_stat[$tid] = ($translations_stat[$tid] ?? 0) + 1;
            }


            $maxFilledTranslationId = array_search(max($translations_stat), $translations_stat);
            $maxFilledFile = null;
            foreach ($files as $file) {
                if ($file['translation_id'] == $maxFilledTranslationId) {
                    $maxFilledFile = $file;
                    break;
                }
            }

            if ($translate) {
                foreach ($files as $file) {
                    if ($translate == $file['translation_id'])
                        $media = $file;
                }
            } else
                $media = $maxFilledFile;

            if (empty($media))
                return "Media not found";

            if (!$translate)
                $translate = $media['translation_id'];

            $translateTitle = $media['t_tag'] ?: $media['t_title'];

        } else { // serial
            $data['is_serial'] = true;
            
            // files
            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->orderBy('season', 'asc')
                ->orderBy('num', 'asc')
                ->get()
                ->toArray();

            if (empty($files)) return "";

            // найти самый наполненный перевод
            $translations_stat = [];
            foreach ($files as $file) {
                $tid = $file['translation_id'];
                $translations_stat[$tid] = ($translations_stat[$tid] ?? 0) + 1;
            }
            $maxFilledTranslationId = array_search(max($translations_stat), $translations_stat);
            $maxFilledFile = null;
            foreach ($files as $file) {
                if ($file['translation_id'] == $maxFilledTranslationId) {
                    $maxFilledFile = $file;
                    break;
                }
            }

            $data['files'] = $files;

            $media = null;

            if (!$translate && !$season && !$episode && !empty($maxFilledFile))
                $media = $maxFilledFile;

            if (isset($maxFilledFile)) {
                foreach ($files as $file) {
                    /*if ($translate && $season && $episode && $translate == $file['translation_id'] && $season == $file['season'] && $episode == $file['num']) {
                        $media = $file;
                        break;
                    } elseif ($season && $episode && $season == $file['season'] && $episode == $file['num']) {
                        $media = $file;
                        break;
                    }

                    if ($translate && $season && !$episode && $translate == $file['translation_id'] && $season == $file['season']) {
                        $media = $file;
                        break;
                    } elseif ($season && !$episode && $season == $file['season']) {
                        $media = $file;
                        break;
                    }

                    if ($translate && !$season && !$episode && $translate == $file['translation_id']) {
                        $media = $file;
                        break;
                    }*/

                    // translation & season & episode
                    if ($translate && $season && $episode && $translate == $file['translation_id'] && $season == $file['season'] && $episode == $file['num']) {
                        $media = $file;
                        break;
                    // translation & season
                    } elseif ($translate && $season && !$episode && $translate == $file['translation_id'] && $season == $file['season']) {
                        $media = $file;
                        break;
                    // translate
                    } elseif ($translate && !$season && !$episode && $translate == $file['translation_id']) {
                        $media = $file;
                        break;
                    // season & episode
                    } elseif (!$translate && $season && $episode && $season == $file['season'] && $episode == $file['num']) {
                        $media = $file;
                        break;
                    // season
                    } elseif (!$translate && $season && !$episode && $season == $file['season']) {
                        $media = $file;
                        break;
                    }
                }
            }

            if (empty($media))
                return "Media not found";

            if (!$translate)
                $translate = $media['translation_id'];

            $translateTitle = $media['t_tag'] ?: $media['t_title'];

            if (!$season)
                $season = $media['season'];

            if (!$episode)
                $episode = $media['num'];

            // seasons_episodes

            $seasons = [];
            $episodes = [];
            $translations_episodes = [];
            $seasons_episodes = [];

            foreach ($files as $file) {
                if ($translate == $file['translation_id']) {
                    // seasons

                    if (!isset($seasons[$file['season']]))
                        $seasons[$file['season']] = $file['season'];

                    // episodes

                    if ($season == $file['season'] && !in_array($file['num'], $episodes)) {
                        $episodes[] = $file['num'];
                    }

                    // seasons episodes

                    if (!isset($seasons_episodes[$file['season']])) {
                        $seasons_episodes[$file['season']] = [];
                    }
                    if (!in_array($file['num'], $seasons_episodes[$file['season']])) {
                        $seasons_episodes[$file['season']][] = $file['num'];
                    }
                }

                // translations seasons episodes

                if (!isset($translations_episodes[$file['translation_id']]))
                    $translations_episodes[$file['translation_id']] = [];

                if (!isset($translations_episodes[$file['translation_id']][$file['season']]))
                    $translations_episodes[$file['translation_id']][$file['season']] = [];

                $translations_episodes[$file['translation_id']][$file['season']][] = $file['num'];
            }
            $data['seasons'] = $seasons;
            $data['episodes'] = $episodes;
            $data['translations_episodes'] = $translations_episodes;
            $data['seasons_episodes'] = $seasons_episodes;

        }

        $data['translate'] = $translate;
        $data['translateTitle'] = $translateTitle;
        $data['season'] = $season;
        $data['episode'] = $episode;
        $data['media'] = $media;
        return "";
    }

    private function inject_translations(array &$data): string {
        $translations = [];
        foreach ($data['files'] as $file) {
            $id = $file['translation_id'];
            if (!isset($translations[$id])) {
                $translations[$id] = [
                    'id'      => $id,
                    'title'   => $file['t_tag'] ?: $file['t_title'],
                ];
                if ($data['is_serial']) {
                    $translations[$id]['episodes_qty'] = 1;
                }
            } else {
                if ($data['is_serial']) {
                    $translations[$id]['episodes_qty']++;
                }
            }
        }
        if ($data['is_serial']) {
            usort($translations, fn($a, $b) => ($b['episodes_qty'] ?? 0) <=> ($a['episodes_qty'] ?? 0));
        }
        $data['translations'] = $translations;
        return "";
    }

    private function inject_files(array &$data): string {
        $result = [];
        $video = $data['video'];
        $media = $data['media'];
        $force_cdn = $data['force_cdn'];
        $data['file'] = '';

        if (empty($media['resolutions'])) {
            return '';
        }

        $resolutions = explode(',', $media['resolutions']);
        sort($resolutions);

        // VDB
        
        if ($media['sids'] == 'VDB') {
            $p720 = false;
            $p720Key = 0;
            $p1080 = false;

            $folder = '';
            // $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $ip = ""; // TODO: ivanezko remove this hack when we will re-sign on cdn
            $date = date('YmdH', strtotime("+1 days"));
            $susuritiKey = $this->keyWin;

            $file = parse_url($media['path']);

            $file['host'] = $this->cdn_host_by_video_id($video['id'], $force_cdn);
            // if (!$file['host']) {
            //     $file['host'] = "cdn0.{$this->cdn_domain}"; // fallback если не удалось найти хост
            // }

            $date = date('YmdH', strtotime("+1 days"));
            $folder = $file['path'];
    
            foreach ($resolutions as $rKey => $resolution) {
                // $hash = md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey);
                $hash = md5($folder.'--'.$date.'-'.$susuritiKey);
                $result[] = "[{$resolution}]{$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/{$resolution}.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/{$resolution}.mp4";
                header("X-Player-".$hash.": ".$folder.'--'.$date.'-'.$susuritiKey);

                if ($resolution == '720') {
                    $p720 = true;
                    $p720Key = $rKey;
                }

                if ($resolution == '1080')
                    $p1080 = true;
            }

            if (!$p1080 && $p720)
                $result[] = "[1080]{$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/720.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/720.mp4";
        }

        // ZCDN

        if ($media['sids'] == 'ZCDN') {
            $p720 = false;
            $p720Key = 0;
            $p1080 = false;

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $deadline = date('YmdH', strtotime('+5 hour'));

            foreach ($resolutions as $rKey => $resolution) {
                $hash = md5("/{$media['path']}_{$resolution}.mp4-{$ip}-{$deadline}-superduperyourcinema");

                $result[] = "[{$resolution}]https://kholoload.acheron.zerocdn.com/{$hash}:{$deadline}/{$media['path']}_{$resolution}.mp4:hls:manifest.m3u8";

                if ($resolution == '720') {
                    $p720 = true;
                    $p720Key = $rKey;
                }

                if ($resolution == '1080')
                    $p1080 = true;
            }

            if (!$p1080 && $p720) {
                $hash = md5("/{$media['path']}_720.mp4-{$ip}-{$deadline}-superduperyourcinema");

                $result[] = "[1080]https://kholoload.acheron.zerocdn.com/{$hash}:{$deadline}/{$media['path']}_720.mp4:hls:manifest.m3u8";
            }
        }

        $result = implode(',', $result);

        $data['file'] = $result;
        return "";
    }

    private function inject_ads(array &$data): string {
        $preroll = [];
        $midroll = [];
        $postroll = [];

        $ads = [
            'preroll' => [],
            'midroll' => [],
            'postroll' => []
        ];

        $_ads = Ad::where('on', '1')->get()->toArray();

        foreach ($_ads as $ad) {
            $ad['body'] .= ((strpos($ad['body'], '?') === false ? '?' : '&') . "khtag={$ad['id']}");
            $ad['body'] = str_replace('(domain)', $this->request->domain, $ad['body']);

            if ($ad['position'] == 'start') {
                $ads['preroll'][] = $ad['body'];
                continue;
            }
            if ($ad['position'] == 'center') {
                $ads['midroll'][] = $ad['body'];
                continue;
            }
            if ($ad['position'] == 'end') {
                $ads['postroll'][] = $ad['body'];
                continue;
            }
        }

        if ($ads['preroll'])
            $preroll = implode(' and ', $ads['preroll']);

        if ($ads['midroll'])
            $midroll[] = [
                'time' => '45%',
                'vast' => implode(' and ', $ads['midroll'])
            ];

        if ($ads['postroll'])
            $postroll[] = [
                'time' => '90%',
                'vast' => implode(' and ', $ads['postroll'])
            ];

        $data['preroll'] = $preroll;
        $data['midroll'] = $midroll;
        $data['postroll'] = $postroll;
        return "";
    }

    private function do_stat($domain) {
        if (empty($domain)) return;
        // TODO: здесь race condition
        $dateNow = date("Y-m-d");
        $stats = [];
        if ($domain->show != '') {
            $stats = json_decode($domain->show, true);
        }
        if (isset($stats[$dateNow])) {
            if (isset($stats[$dateNow]['start']))
                $stats[$dateNow]['start'] += 1;
            else
                $stats[$dateNow]['start'] = 1;

            if (!Cookie::has('startuniq'.date('Ymd'))) {
                if (isset($stats[$dateNow]['startuniq']))
                    $stats[$dateNow]['startuniq'] += 1;
                else
                    $stats[$dateNow]['startuniq'] = 1;

                Cookie::queue(Cookie::make('startuniq'.date('Ymd'), '1', 1440, '/', null, true, false, false, 'none'));
            }
        } else {
            $stats[$dateNow] = array(
                'start' => 1,
                'startuniq' => 1,
                'show' => 0,
                'lowshow' => 0,
                'showads' => 0,
            );

            Cookie::queue(Cookie::make('startuniq'.date('Ymd'), '1', 1440, '/', null, true, false, false, 'none'));
        }
        $stats = json_encode($stats);
        Domain::where('id', $domain->id)->update(['show' => $stats ]);
    }

    // cdn_host_by_video_id - возвращает хост CDN для видео
    private function cdn_host_by_video_id(int $video_id, int $force_cdn = null): ?string {
        if ($force_cdn) {
            $cdn_host = "cdn{$force_cdn}.{$this->cdn_domain}";
            $cdn = Cdn::where('host', $cdn_host)->first();
            header("X-Player-cdn-method: force");
            if ($cdn) {
                return $cdn->host;
            }
            return "cdn {$cdn_host} not found in db";
        }

        // есть связка видеоид-сдн?
        $cdnVideo = CdnVideo::select('cdn_id')->where('video_id', $video_id)->first();
        $this->reduce_all_cdns_weight();
        if ($cdnVideo) {
            // есть. проверяем живой ли сдн
            $cdn = Cdn::where('id', $cdnVideo->cdn_id)->where('active', 1)->first();
            if ($cdn) {
                // обновляем счетчик распределенных на этот сдн видосов
                Cdn::where('id', $cdn->id)->update([
                    'counter' => DB::raw('counter+1'),
                    'weight_counter' => DB::raw('weight_counter+1')
                ]);
                CdnVideo::where('video_id', $video_id)->update([
                    'counter' => DB::raw('counter+1')
                ]);
                header("X-Player-cdn-method: stale");
                return $cdn->host;
            }
        }
        // нет назначенного ранее сдн либо он отключен. выбираем новый
        $cdn = Cdn::where('active', 1)->orderBy('weight_counter', 'asc')->first();
        if ($cdn) {
            CdnVideo::updateOrCreate(
                ['video_id' => $video_id],    // что ищем
                ['cdn_id'   => $cdn->id]      // что обновляем
            );
            CdnVideo::where('video_id', $video_id)->update([
                'counter' => DB::raw('counter+1')
            ]); 
            Cdn::where('id', $cdn->id)->update([
                'counter' => DB::raw('counter+1'),
                'weight_counter' => DB::raw('weight_counter+1')
            ]);
            header("X-Player-cdn-method: new");
            return $cdn->host;
        }
        // не удалось выбрать новый
        // TODO: логирование ошибки
        header("X-Player-cdn-method: error");
        return null;
    }

    // reduce_cdn_weight уменьгает взвешенный счетчик примерно каждый 100 вызов
    private function reduce_all_cdns_weight() {
        if (rand() % 100 == 0) {
            Cdn::where('weight_counter', '>', 0)
                ->update([
                    'weight_counter' => DB::raw('CEIL(weight_counter - weight_counter/5)')
                ]);
        }
    }

    // static page for telegram apps - all the params should be passed as #fragment
    public function tgApp()
    {
        // https://t.me/flix_test_bot/applink1?startapp=de8355539ff010db39002c3ad95a65b68516d3e9cf078896120f48db7bd13b51
        // 
        // startapp - слабо зашифрованный JSON с id видео и tgc канала и в будущем другими параметрами
        // $encoded = bin2hex(
        //     openssl_encrypt('{"id":1,"tgc":"@test"}', 'AES-128-ECB', "pray_for_ukraine", OPENSSL_RAW_DATA)
        // );
        // var_dump($encoded);
        // die();

        $startapp = $this->request->input('startapp');
        if (empty($startapp)) { // not sure we need this, but just in case
            $startapp = $this->request->input('tgWebAppStartParam');
        }


        if (!\is_string($startapp) || $startapp === '' || (strlen($startapp) % 2) !== 0 || !ctype_xdigit($startapp)) {
            // неправильный hex
            abort(404);
        }

        $startapp = openssl_decrypt(
            hex2bin($startapp), 'AES-128-ECB', "pray_for_ukraine", OPENSSL_RAW_DATA
        );
        // var_dump($startapp);
        // die();
        $startapp = json_decode($startapp, true);

        if (!is_array($startapp) || !isset($startapp['tgc']) || !isset($startapp['id'])) {
            // неправильный JSON
            abort(404);
        }

        $tgc = str_replace('@', '', $startapp['tgc']);
        $domain = Domain::select()->where('name', "@{$tgc}")->where('status', 1)->first();
        $video = Video::where('id', $startapp['id'])->first();
        if (!$video) {
            abort(404);
        }
        $tgShareDomain = $this->tg_share_domain;

        return view('share', [
            'id' => $startapp['id'],
            'tgc' => "@{$tgc}",
            'domain' => $domain,
            'playerDomain' => $this->cdnhub_player_domain,
            'tgShareDomain' => $tgShareDomain,
            'cdnPublicDomain' => $this->cdnhub_public_domain,
            'video' => $video,
            'views' => 0,
            'share' => rawurlencode("https://{$tgShareDomain}/share/{$startapp['id']}?tgc={$tgc}"),
            'title' => rawurlencode($video->ru_name . ($video->year ? ' (' . $video->year . ')' : '') . ' смотреть в HD онлайн'),
            'image' => rawurlencode("https://{$tgShareDomain}/share/share.jpg")
        ]);
    }
    public function share($id)
    {
        if ($this->request->input('tgc'))
            $tgc = rawurldecode($this->request->input('tgc'));
        else
            $tgc = null;

        $tgc = str_replace('@', '', $tgc);
        $domain = Domain::select()->where('name', "@{$tgc}")->where('status', 1)->first();
        $video = Video::where('id', $id)->first();
        if (!$video) {
            abort(404);
        }

        $views = 0;

        if (!empty($domain)) {
            $show = Show::select()->where('id_domain', $domain->id)->where('id_video', $id)->first();
            if ($show) {
                $views = $show->shows;
                Show::where('id_domain', $domain->id)->where('id_video', $id)->update([
                    'shows' => $show->shows + 1
                ]);
            } else {
                Show::create([
                    'id_domain' => $domain->id,
                    'id_video' => $id,
                    'id_ad' => 0,
                    'shows' => 1
                ]);
            }
        }

        $tgShareDomain = $this->tg_share_domain;

        return view('share', [
            'id' => $id,
            'tgc' => "@{$tgc}",
            'domain' => $domain,
            'playerDomain' => $this->cdnhub_player_domain,
            'tgShareDomain' => $tgShareDomain,
            'cdnPublicDomain' => $this->cdnhub_public_domain,
            'video' => $video,
            'views' => $views,
            'share' => rawurlencode("https://{$tgShareDomain}/share/{$id}?tgc={$tgc}"),
            'title' => rawurlencode($video->ru_name . ($video->year ? ' (' . $video->year . ')' : '') . ' смотреть в HD онлайн'),
            'image' => rawurlencode("https://{$tgShareDomain}/share/share.jpg")
        ]);
    }

    public function download($video_id)
    {
        // dd($this->request->all());

        $secret = $this->cloudflare_captcha_secret; // из Cloudflare Turnstile
        $token  = $this->request->input('cf-turnstile-response') ?? null;
        $remoteIp = null; //$_SERVER['REMOTE_ADDR'] ?? null;
        $translation_id = $this->request->input('translation_id') ?? null;
        $season = $this->request->input('season') ?? null;
        $episode = $this->request->input('episode') ?? null;
        $skip_captcha_check = $this->request->input('skip_captcha_check') ?? null;
        $skip_captcha_check = ($skip_captcha_check == 'FHJFGFJHJHDDUERU77734HHGJJG') ? true : false;


        if (!$translation_id) {
            return response('Give me translation_id', 400);
        }

        if (!$secret) {
            return response('No secret found', 503);
        }
        if (!$token) {
            return response('Give me cf-turnstile-response', 400);
            
        }
        // if (!$remoteIp) {
        //     abort(403, 'No remote_addr found');
        // }

        $isCaptchaValid = Cloudflare::check_captcha($token, $remoteIp, $secret);

        if (!$skip_captcha_check && !$isCaptchaValid) {
            return response('Captcha validation failed', 403);
        }

        $video = Video::where('id', $video_id)->first();

        if (!$video) {
            return response('Video not found', 404);
        }

        $media = File::where('id_parent', $video_id)
            ->where('translation_id', $translation_id)
            ->where('season', $season)
            ->where('num', $episode)
            ->first();

        if (!$media) {
            return response('Media not found', 404);
        }

        $resolutions = explode(',', $media['resolutions']);
        $target_resolution = array_pop($resolutions); // выбираем наименьшее доступное разрешение

        $file = parse_url($media['path']);

        $file['host'] = $this->cdn_host_by_video_id($video['id'] );
        // if (!$file['host']) {
        //     $file['host'] = "cdn0.{$this->cdn_domain}"; // fallback если не удалось найти хост
        // }

        $date = date('YmdH', strtotime("+1 hours"));
        $folder = $file['path'];

        $susuritiKey = $this->keyWin;
        $hash = md5($folder.'--'.$date.'-'.$susuritiKey);
        $file_url = "{$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/{$target_resolution}.mp4";
        $file_name = $video["ru_name"];
        if ($media["season"] != 0) { 
            $file_name .= "_S{$media['season']}";
        }
        if ($media["num"] != 0) { 
            $file_name .= "_E{$media['num']}";
        }
        $file_name .= "_{$target_resolution}p";
        $file_name .= ".mp4";
        $file_name = preg_replace(
            '/[^0-9A-Za-zА-Яа-яЁё\s\.\-_]+/u',
            '',
            $file_name
        );
        return response("{$file_url}?filename={$file_name}", 200);
        // return response()->download($file_url);
        // return redirect()->away($file_url); // 302 редирект на CDN
    }


}


