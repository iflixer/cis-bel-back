<?php

namespace App\Http\Controllers;

use App\Services\LocationTracker;
use Illuminate\Http\Request;

use App\Http\Requests;

use App\File;
use App\Video;

use App\Seting;
use App\Translation;
use App\Videodb;
use App\Ad;

use App\Domain;
use App\Cdn;
use App\CdnVideo;

use Illuminate\Support\Facades\DB;

use App\Show;

use Cookie;

class ShowController extends Controller{

    protected $loginVDB;
    protected $passVDB;
    

    // public function show(Request $request, $id){


    //     $lockMass = [
    //         'RU' => 'RU',
    //         'UA' => 'UA',
    //         'SNG' => 'AZ,AM,BY,KZ,KG,MD,TJ,UZ,TM'
    //     ];

    //     $video = Video::where('id', $id)->first();

    //     if(isset($video) && $video->lock != null && $video->lock != ''){
    //         $lock = explode(',',$video->lock);
    //         $ipData = json_decode(file_get_contents('http://ipinfo.io/'.$_SERVER['HTTP_X_FORWARDED_FOR'].'?token=81e9b5a1120863'), true); 
    //         foreach ($lock as $value) {
    //             if($value == 'FULL' ||   ( isset($lockMass[$value]) && strpos($lockMass[$value], $ipData['country']) !== false)  ){
    //                 abort(423);
    //             }
    //         }
    //     }

        




    //     if( isset($video) ){

    //         $name = $video->ru_name;
    //         $url = '';
    //         $filess = [];
    //         $sesons = [];
    //         $translations = [];

    //         $folder = '';
    //         $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    //         $date = date('YmdH', strtotime("+1 days"));
    //         $susuritiKey = 'mB19SrYdROqY';

            
    //         $path = '';
    //         $playList = [];


    //         if($video->tupe == 'movie'){

    //             // dump( File::where('id_parent', $id)->get() );

    //             $files = File::where('id_parent', $id)->first();
    //             $file = parse_url($files->path);

    //             $folder = $file['path'];
    //             $url =  $file['scheme'] .'://'. $file['host'] . $folder. md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey).':'.$date.'/720.mp4';
    //             $path = $file['scheme'] . '://' . $file['host'] . $folder . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey).':'.$date.'/';


    //             $playList = $file['scheme'] .'://'. $file['host'] . $folder . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey).':'.$date.'/hls.m3u8';

    //         }else{

    //             $files = File::where('id_parent', $id)->get();

    //             foreach ($files as $value) {
    //                 $translations[] = $value->translation;
    //                 $translations = array_unique($translations);
    //                 sort($translations);
    //             }
    //             $playList = array_map(function($var){ return ["title" => $var, "folder" => []]; } ,$translations);

    //             foreach ($files as $value) {
    //                 foreach ($playList as $key => $valueFolder){
    //                     $playList[$key]['folder'][] = $value->season;

    //                     $playList[$key]['folder'] = array_unique($playList[$key]['folder']);
    //                     sort($playList[$key]['folder']);
    //                 }
    //             }

    //             $playList = array_map(function($var){ 
    //                 return ["title" => $var['title'], "folder" => array_map(function($var2){ 
    //                     return ["title" => $var2, "folder" => []]; 
    //                 }, $var['folder']) ]; 
    //             } ,$playList);

    //             foreach ($files as $value) {
    //                 foreach ($playList as $key => $translation){
    //                     if($translation['title'] == $value->translation){
    //                         foreach($playList[$key]['folder'] as $keyj => $season){
    //                             if($season['title'] == $value->season){
    //                                 $file = parse_url($value->path);
    //                                 $date = date('YmdH', strtotime("+1 days"));
    //                                 $folder = $file['path'];

    //                                 $playList[$key]['folder'][$keyj]['folder'][] = [ 
    //                                     "title" => $value->ru_name, 
    //                                     "file" =>   $file['scheme'] .'://'. $file['host'] . $folder . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey).':'.$date.'/hls.m3u8'
    //                                 ];
    //                                 break;
    //                             }
    //                         }
    //                         break;
    //                     }
    //                 }
    //             }
        
    //         }



            

    //         // $recvaer = $file['scheme'] . '://' . $file['host'] . $folder . md5($folder.'-'.$ip.'-'.$date.'-'.$key).':'.$date.'/720.mp4';


    //         return view('show', [ 
    //             'dataPlayer' => $request->player,
    //             'domain' => $request->domain,
    //             'playList' => json_encode($playList)
    //         ]);
    //     }


    //     abort(404);
    // }

    public function __construct(Request $request){
        $this->request = $request;
        // $this->loginVDB = config('videodb.login');
        // $this->passVDB = config('videodb.password');
        // $this->keyWin = config('videodb.key_win');
        $this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
        $this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
        $this->keyWin = Seting::where('name', 'keyWin')->first()->toArray()['value'];
    }

    public function player($type = null, $id = 0)
    {

        if (
            $this->request->domain == 'apiget.ru'
            || ($this->request->domain == 'kholobok.biz' && $_SERVER['HTTP_USER_AGENT'] == 'okhttp/4.9.0')
        ) {
            http_response_code(404);
            exit;
        }

        $lockMass = [
            'RU' => 'RU',
            'UA' => 'UA',
            'SNG' => 'AZ,AM,BY,KZ,KG,MD,TJ,UZ,TM'
        ];

        $data = [];

        // video

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


        LocationTracker::logPlayerRequestFromHeaders($video->id, $this->request->domain);

        if (isset($video) && $video->lock != null && $video->lock != '') {
            $lock = explode(',',$video->lock);
            //$ipSource = @file_get_contents('http://ipinfo.io/'.$_SERVER['HTTP_X_FORWARDED_FOR'].'?token=81e9b5a1120863');
            $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
            if ($country) {
                //$ipData = json_decode($ipSource, true);
                // $country = $ipData['country'];
                foreach ($lock as $value) {
                    if ($value == 'FULL' || ( isset($lockMass[$value]) && strpos($lockMass[$value], $country ) !== false)) {
                        abort(423);
                    }
                }
            }
        }

        $video->toArray();

        $data['video'] = $video;


        // check domain in parameters

        /*if (isset($_GET['domain']) && $_GET['domain'] && preg_match("#^[a-z0-9-_.]+$#i", $_GET['domain'])) {
            $this->request->domain = $_GET['domain'];
        }*/

        // tgc

        if ($this->request->input('tgc'))
            $tgc = $this->request->input('tgc');
        else
            $tgc = null;

        if ($tgc)
            $this->request->domain = "@{$tgc}";

        $data['tgc'] = $tgc;

        // video id

        $data['id'] = $video['id'];

        // video type

        if ($video['tupe'] == 'movie')
            $data['type'] = 'movie';
        elseif ($video['tupe'] == 'episode')
            $data['type'] = 'serial';

        // input autoplay

        if ($this->request->input('autoplay') && intval($this->request->input('autoplay')))
            $autoplay = true;
        else
            $autoplay = false;

        if ($autoplay)
            $data['autoplay'] = 1;
        else
            $data['autoplay'] = 0;

        // input start

        if ($this->request->input('start') && intval($this->request->input('start')))
            $start = intval($this->request->input('start'));
        else
            $start = 0;

        $data['start'] = $start;

        // input translate

        if ($this->request->input('translation') && intval($this->request->input('translation')))
            $translate = intval($this->request->input('translation'));
        else
            $translate = null;

        // input season

        if ($this->request->input('season') && intval($this->request->input('season')))
            $season = intval($this->request->input('season'));
        else
            $season = null;

        // input episode

        if ($this->request->input('episode') && intval($this->request->input('episode')))
            $episode = intval($this->request->input('episode'));
        else
            $episode = null;

        // movie
        $data = $this->inject_media($data,  $translate, $season, $episode);

        $data = $this->inject_translations($data);

        $data = $this->inject_files($data);

        $data = $this->inject_ads($data);


        $domain = Domain::where('name', $this->request->domain)->first();

        $this->do_stat($domain);

        $player_view = 'player';
        if ($domain->player_view) {
            $player_view = $domain->player_view;
        } else {
            $player_view_global = Seting::where('name', 'player_view')->first()->toArray()['value'];
            if ($player_view_global) {
                $player_view = $player_view_global;
            }
        }
        

        return view($player_view, $data);
    }

    private function inject_media(array $data, $translate, $season, $episode): array {
        $video = $data['video'];
        $id = $video['id'];
        if ($video['tupe'] == 'movie') {
            
            // files

            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();

            $data['files'] = $files;

            // media

            if ($translate) {
                foreach ($files as $file) {
                    if ($translate == $file['translation_id'])
                        $media = $file;
                }
            } else
                $media = $files[0];

            if (empty($media))
                abort(404);

            if (!$translate)
                $translate = $media['translation_id'];

            $translateTitle = $media['t_tag'] ?: $media['t_title'];

        }

        // serial

        if ($video['tupe'] == 'episode') {
            
            // files

            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->orderBy('season', 'asc')
                ->orderBy('num', 'asc')
                ->get()
                ->toArray();

            $data['files'] = $files;

            $media = null;

            if (!$translate && !$season && !$episode && isset($files[0]))
                $media = $files[0];

            if (isset($files[0])) {
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
                abort(404);

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

                    if ($season == $file['season'])
                        $episodes[] = $file['num'];

                    // seasons episodes

                    if (!isset($seasons_episodes[$file['season']]))
                        $seasons_episodes[$file['season']] = [];

                    $seasons_episodes[$file['season']][] = $file['num'];
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
        return $data;
    }

    private function inject_translations(array $data): array {
        $files = $data['files'];
        $translations = [];
        foreach ($data['files'] as $file) {
            if (!isset($translations[$file['translation_id']]))
                $translations[$file['translation_id']] = [
                    'id' => $file['translation_id'],
                    'title' => $file['t_tag'] ?: $file['t_title']
                ];
        }
        $translations = array_values($translations);
        $data['translations'] = $translations;
        return $data;
    }

    private function inject_files(array $data): array {
        $result = [];
        $video = $data['video'];
        $media = $data['media'];
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

            // TODO: ivanezko refactor to store the host in DB
            $file['host'] = $this->cdn_host_by_video_id($video['id']);
            if (!$file['host']) {
                $file['host'] = "cdn0.testme.wiki"; // fallback если не удалось найти хост
            }

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
        return $data;
    }

    private function inject_ads(array $data): array {
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
        return $data;
    }

    private function do_stat($domain) {
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
    private function cdn_host_by_video_id($video_id): ?string {
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
            return $cdn->host;
        }
        // не удалось выбрать новый
        // TODO: логирование ошибки
        return null;
    }

    // reduce_cdn_weight обновляет взвешенный счетчик примерно каждый 100 вызов
    private function reduce_all_cdns_weight() {
        if (rand() % 100 == 0) {
            Cdn::where('weight_counter', '>', 0)
                ->update([
                    'weight_counter' => DB::raw('CEIL(weight_counter - weight_counter/10)')
                ]);
        }
    }
    public function share($id)
    {
        if ($this->request->input('tgc'))
            $tgc = rawurldecode($this->request->input('tgc'));
        else
            $tgc = null;

        if (!$tgc) {
            abort(404);
        }

        $domain = Domain::select()->where('name', "@{$tgc}")->where('status', 1)->first();

        if (!$domain) {
            abort(404);
        }

        $video = Video::where('id', $id)->first();

        if (!$video) {
            abort(404);
        }

        $views = 0;

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

        $serverDomain = 'tg.cdnhubstream.pro';

        return view('share', [
            'id' => $id,
            'tgc' => $tgc,
            'domain' => $domain,
            'serverDomain' => $serverDomain,
            'video' => $video,
            'views' => $views,
            'share' => rawurlencode("https://{$serverDomain}/share/{$id}?tgc={$tgc}"),
            'title' => rawurlencode($video->ru_name . ($video->year ? ' (' . $video->year . ')' : '') . ' смотреть в HD онлайн'),
            'image' => rawurlencode("https://{$serverDomain}/share/share.jpg")
        ]);
    }


}


