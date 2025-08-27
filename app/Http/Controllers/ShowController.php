<?php

namespace App\Http\Controllers;

use App\Services\LocationTracker;
use Illuminate\Http\Request;

use App\Http\Requests;

use App\File;
use App\Video;

use App\Translation;
use App\Videodb;
use App\Ad;

use App\Domain;

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

        $this->keyWin = config('videodb.key_win');

        $this->loginVDB = config('videodb.login');
        $this->passVDB = config('videodb.password');
    }

    public function newshow(Request $request, $id){

        exit;

        $lockMass = [
            'RU' => 'RU',
            'UA' => 'UA',
            'SNG' => 'AZ,AM,BY,KZ,KG,MD,TJ,UZ,TM'
        ];

        $video = Video::where('id', $id)->first();

        if(isset($video) && $video->lock != null && $video->lock != ''){
            $lock = explode(',',$video->lock);
            // $ipData = json_decode(file_get_contents('http://ipinfo.io/'.$_SERVER['HTTP_X_FORWARDED_FOR'].'?token=81e9b5a1120863'), true); 
            $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
            foreach ($lock as $value) {
                if($value == 'FULL' ||   ( isset($lockMass[$value]) && strpos($lockMass[$value], $country) !== false)  ){
                    abort(423);
                }
            }
        }


        if( isset($video) ){

            $name = $video->ru_name;

            $url = '';

            $filess = [];
            $sesons = [];
            $translations = [];

            $folder = '';
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $date = date('YmdH', strtotime("+1 days"));
            $susuritiKey = $this->keyWin; // 'mB19SrYdROqY'; mB19SrYdROqY

            $path = '';
            $playList = [];



            // $files = File::where('id_parent', $id)->get();
            $files = File::distinct()->select('files.*', 'translations.tag', 'translations.priority')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'DESC')
                ->get();

            // dump($files);

            foreach ($files as $value) {
                // $translations[] = $value->translation;
                $translations[] = $value->tag ?: $value->translation;
                $translations = array_unique($translations);
                // sort($translations);
            }

            $translations = array_values($translations);

            $playList = array_map(function($var){ return ["title" => $var, "folder" => []]; } ,$translations);

            foreach ($files as $value) {
                foreach ($playList as $key => $valueFolder){
                    $playList[$key]['folder'][] = $value->season;

                    $playList[$key]['folder'] = array_unique($playList[$key]['folder']);
                    sort($playList[$key]['folder']);
                }
            }

            $playList = array_map(function($var){ 
                return ["title" => $var['title'], "folder" => array_map(function($var2){ 
                    return ["title" => $var2, "folder" => []]; 
                }, $var['folder']) ]; 
            } ,$playList);

            foreach ($files as $value) {
                foreach ($playList as $key => $translation){
                    // if($translation['title'] == $value->translation){
                    if ($translation['title'] == ($value->tag ?: $value->translation)) {
                        foreach($playList[$key]['folder'] as $keyj => $season){
                            if($season['title'] == $value->season){
                                $file = parse_url($value->path);
                                $date = date('YmdH', strtotime("+1 days"));
                                $folder = $file['path'];

                                $playList[$key]['folder'][$keyj]['folder'][] = [ 
                                    // "title" => $value->ru_name,
                                    "title" => $value->num,
                                    "file" =>   $file['scheme'] .'://'. $file['host'] . $folder . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey).':'.$date.'/hls.m3u8'
                                ];
                                break;
                            }
                        }
                        break;
                    }
                }
            }


            // playlist build fix

                foreach ($playList as $t_key => $translation) {
                    foreach ($translation['folder'] as $s_key => $season) {
                        if (!$season || !$season['folder'] || count($season['folder']) === 0)
                            unset($playList[$t_key]['folder'][$s_key]);
                    }
                }

            // playlist structure fix

                $playList = array_values($playList);
                foreach ($playList as $t_key => $translation)
                    $playList[$t_key]['folder'] = array_values($playList[$t_key]['folder']);


            // dump($files);

            return view('newshow', [ 
                'dataplayer' => $request->player,
                'systeminfo' => json_encode([ 
                    'id' => $id, 
                    'key' => 'h8dg28ks', 
                    'files' => $files,
                    'playList' => $playList,
                    'domain' => $request->domain
                ])
            ]);

        }
        abort(404);
    }

    // fallback_player used to show collapse player if we cant show ours
    private function fallback_player($kp_id) {
        header("X-CDNHub-fallback: fallback");
        return view('show.collapse', [ 
                'kp_id' => $kp_id
            ]);
    }

    public function player($type = null, $id = 0)
    {
        // filter begin

        $serverInfo = [
            'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : null,
            'SERVER_NAME' => isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null,
            'SERVER_PROTOCOL' => isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : null,

            'REQUEST_METHOD' => isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : null,
            'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : null,
            'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : null,

            'HTTP_X_REAL_IP' => isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] ? $_SERVER['HTTP_X_REAL_IP'] : null,
            'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null,
            'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null,

            'HTTP_COOKIE' => isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE'] ? $_SERVER['HTTP_COOKIE'] : null,

            'HTTP_SEC_CH_UA' => isset($_SERVER['HTTP_SEC_CH_UA']) && $_SERVER['HTTP_SEC_CH_UA'] ? $_SERVER['HTTP_SEC_CH_UA'] : null,
            'HTTP_SEC_CH_UA_MOBILE' => isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ? $_SERVER['HTTP_SEC_CH_UA_MOBILE'] : null,
            'HTTP_SEC_CH_UA_PLATFORM' => isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) && $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] : null,

            'HTTP_UPGRADE_INSECURE_REQUESTS' => isset($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) && $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ? $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] : null,

            'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] ? $_SERVER['HTTP_ACCEPT'] : null,
            'HTTP_ACCEPT_ENCODING' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $_SERVER['HTTP_ACCEPT_ENCODING'] ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
            'HTTP_ACCEPT_LANGUAGE' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
        ];

        if (
            $this->request->domain == 'apiget.ru'
            || ($this->request->domain == 'kholobok.biz' && $_SERVER['HTTP_USER_AGENT'] == 'okhttp/4.9.0')
        ) {
            http_response_code(404);
            exit;
        }

        /*$serverInfo = var_export($serverInfo, true);
        $serverInfo = str_replace('array (', '', $serverInfo);
        $serverInfo = rtrim($serverInfo, ")\r");

        $serverInfoData = date('d.m.Y H:i:s') . (isset($this->request->domain) ? ' ' . $this->request->domain : ' undefined') . "\r{$serverInfo}\r\r";

        file_put_contents(__DIR__ . '/filterNetworkTraffic.log', $serverInfoData, LOCK_EX | FILE_APPEND);*/

        // filter end

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
                // header("X-CDNHub-error: Video not found");
                // header("X-CDNHub-type: ".$type);
                // header("X-CDNHub-id: ".$id);
                // abort(404);
                $this->fallback_player($id);
            }
            $id = $video->id;
        } else {
            if (!$id) {
                $id = $type;
            }

            $video = Video::where('id', $id)->first();
        }

        if (!$video) {
            // abort(404);
            $this->fallback_player($id);
        }

        LocationTracker::logPlayerRequestFromHeaders($video->id, $this->request->domain);

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {
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
        // }

        $video->toArray();

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

        if ($video['tupe'] == 'movie') {
            
            // files

            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();

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

            // media

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

        // translations

        $translations = [];

        foreach ($files as $file) {
            if (!isset($translations[$file['translation_id']]))
                $translations[$file['translation_id']] = [
                    'id' => $file['translation_id'],
                    'title' => $file['t_tag'] ?: $file['t_title']
                ];
        }

        $translations = array_values($translations);

        $data['translations'] = $translations;

        // build media

        $resolutions = explode(',', $media['resolutions']);
        sort($resolutions);

        $result = [];

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
            $file['host'] = "cdn1.testme.wiki";

            $date = date('YmdH', strtotime("+1 days"));
            $folder = $file['path'];
    
            /*if ($this->request->input('debug') && $video['kinopoisk']) {

                print_r($media);

                $ch = curl_init("{$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-185.4.65.227-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolutions[0]}.mp4:hls:manifest.m3u8");

                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1.5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);

                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $ch_result = curl_exec($ch);

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($http_code == '404') {
                    
                    //

                }

            }*/

            

            foreach ($resolutions as $rKey => $resolution) {
                $hash = md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey);
                $result[] = "[{$resolution}]{$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/{$resolution}.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . $hash . ":{$date}/{$resolution}.mp4";
                header("X-Player-".$hash.": ".$folder.'-'.$ip.'-'.$date);

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

        // filter file begin

        /*$serverInfo = [
            'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : null,
            'SERVER_NAME' => isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null,
            'SERVER_PROTOCOL' => isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : null,

            'REQUEST_METHOD' => isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : null,
            'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : null,
            'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : null,

            'HTTP_X_REAL_IP' => isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] ? $_SERVER['HTTP_X_REAL_IP'] : null,
            'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null,
            'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null,

            'HTTP_COOKIE' => isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE'] ? $_SERVER['HTTP_COOKIE'] : null,

            'HTTP_SEC_CH_UA' => isset($_SERVER['HTTP_SEC_CH_UA']) && $_SERVER['HTTP_SEC_CH_UA'] ? $_SERVER['HTTP_SEC_CH_UA'] : null,
            'HTTP_SEC_CH_UA_MOBILE' => isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ? $_SERVER['HTTP_SEC_CH_UA_MOBILE'] : null,
            'HTTP_SEC_CH_UA_PLATFORM' => isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) && $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] : null,

            'HTTP_UPGRADE_INSECURE_REQUESTS' => isset($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) && $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ? $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] : null,

            'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] ? $_SERVER['HTTP_ACCEPT'] : null,
            'HTTP_ACCEPT_ENCODING' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $_SERVER['HTTP_ACCEPT_ENCODING'] ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
            'HTTP_ACCEPT_LANGUAGE' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
        ];

        if (
            $this->request->domain == 'apiget.ru'
            || ($this->request->domain == 'kholobok.biz' && $_SERVER['HTTP_USER_AGENT'] == 'okhttp/4.9.0')
        ) {
            $data['file'] = '[360]https://cdn0.futemaxlive.com/ShyThief.mp4,[480]https://cdn0.futemaxlive.com/ShyThief.mp4,[720]https://cdn0.futemaxlive.com/ShyThief.mp4,[1080]https://cdn0.futemaxlive.com/ShyThief.mp4';
        }*/

        // filter file end

        // ads

        $preroll = null;
        $midroll = null;

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {

            $ads = [
                'preroll' => null,
                'midroll' => null,
                'postroll' => null
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
                $midroll[] = [
                    'time' => '90%',
                    'vast' => implode(' and ', $ads['postroll'])
                ];

        // }

        /*if (isset($_GET['debug'])) {
            $preroll = 'https://vastroll.ru/vast/vpaid.php?pl=10069&domain_ref=dle.testkholobok.ru';
        }*/
        
        /*if (isset($_GET['test'])) {
            $preroll = 'https://api.kholobok.biz/vast.xml?v=4.0';
        }*/

        $data['preroll'] = $preroll;
        $data['midroll'] = $midroll;

        // start

        /*$serverInfo = [
            'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : null,
            'SERVER_NAME' => isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null,
            'SERVER_PROTOCOL' => isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : null,

            'REQUEST_METHOD' => isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : null,
            'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : null,
            'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : null,

            'HTTP_X_REAL_IP' => isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] ? $_SERVER['HTTP_X_REAL_IP'] : null,
            'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null,
            'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null,

            'HTTP_COOKIE' => isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE'] ? $_SERVER['HTTP_COOKIE'] : null,

            'HTTP_SEC_CH_UA' => isset($_SERVER['HTTP_SEC_CH_UA']) && $_SERVER['HTTP_SEC_CH_UA'] ? $_SERVER['HTTP_SEC_CH_UA'] : null,
            'HTTP_SEC_CH_UA_MOBILE' => isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ? $_SERVER['HTTP_SEC_CH_UA_MOBILE'] : null,
            'HTTP_SEC_CH_UA_PLATFORM' => isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) && $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] : null,

            'HTTP_UPGRADE_INSECURE_REQUESTS' => isset($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) && $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ? $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] : null,

            'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] ? $_SERVER['HTTP_ACCEPT'] : null,
            'HTTP_ACCEPT_ENCODING' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $_SERVER['HTTP_ACCEPT_ENCODING'] ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
            'HTTP_ACCEPT_LANGUAGE' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
        ];

        $serverInfo = var_export($serverInfo, true);
        $serverInfo = str_replace('array (', '', $serverInfo);
        $serverInfo = rtrim($serverInfo, ")\r");

        $serverInfoData = date('d.m.Y H:i:s') . (isset($this->request->domain) ? ' ' . $this->request->domain : ' undefined') . "\r{$serverInfo}\r\r";

        file_put_contents(__DIR__ . '/filterNetworkTraffic.log', $serverInfoData, LOCK_EX | FILE_APPEND);*/

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {

            $dateNow = date("Y-m-d");

            $domainStats = Domain::select('show')->where('name', $this->request->domain)->first();

            if ($domainStats) {
                $stats = [];
                if ($domainStats->show != '') {
                    $stats = json_decode($domainStats->show, true);
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
                Domain::where('name', $this->request->domain)->update(['show' => $stats ]);
            }

        // }

        return view('player', $data);
    }

    public function player2($type = null, $id = 0)
    {
        exit;

        $lockMass = [
            'RU' => 'RU',
            'UA' => 'UA',
            'SNG' => 'AZ,AM,BY,KZ,KG,MD,TJ,UZ,TM'
        ];

        $data = [];

        // video

        if ($type && $id) {
            $video = Video::where($type, $id)->first();
            $id = $video->id;
        } else {
            if (!$id) {
                $id = $type;
            }

            $video = Video::where('id', $id)->first();
        }

        if (!$video)
            abort(404);

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {
            if (isset($video) && $video->lock != null && $video->lock != '') {
                $lock = explode(',',$video->lock);
                // $ipData = json_decode(file_get_contents('http://ipinfo.io/'.$_SERVER['HTTP_X_FORWARDED_FOR'].'?token=81e9b5a1120863'), true); 
                $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
                foreach ($lock as $value) {
                    if ($value == 'FULL' || ( isset($lockMass[$value]) && strpos($lockMass[$value], $country) !== false)) {
                        abort(423);
                    }
                }
            }
        // }

        $video->toArray();

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

        if ($video['tupe'] == 'movie') {
            
            // files

            $files = File::select('files.*', 'translations.title as t_title', 'translations.tag as t_tag')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->get()
                ->toArray();

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

            // media

            $media = null;

            if (!$translate && !$season && !$episode && isset($files[0]))
                $media = $files[0];

            if (isset($files[0])) {
                foreach ($files as $file) {
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

        // translations

        $translations = [];

        foreach ($files as $file) {
            if (!isset($translations[$file['translation_id']]))
                $translations[$file['translation_id']] = [
                    'id' => $file['translation_id'],
                    'title' => $file['t_tag'] ?: $file['t_title']
                ];
        }

        $translations = array_values($translations);

        $data['translations'] = $translations;

        // build media

        $resolutions = explode(',', $media['resolutions']);
        sort($resolutions);

        $result = [];

        // VDB
        
        if ($media['sids'] == 'VDB') {
            $p720 = false;
            $p720Key = 0;
            $p1080 = false;

            $folder = '';
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $date = date('YmdH', strtotime("+1 days"));
            $susuritiKey = $this->keyWin;

            $file = parse_url($media['path']);
            $date = date('YmdH', strtotime("+1 days"));
            $folder = $file['path'];

            /*if ($this->request->input('debug') && $video['kinopoisk']) {

                print_r($media);

                $ch = curl_init("{$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-185.4.65.227-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolutions[0]}.mp4:hls:manifest.m3u8");

                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1.5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);

                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $ch_result = curl_exec($ch);

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($http_code == '404') {
                    
                    //

                }

            }*/

            foreach ($resolutions as $rKey => $resolution) {
                $result[] = "[{$resolution}]{$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolution}.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolution}.mp4";

                if ($resolution == '720') {
                    $p720 = true;
                    $p720Key = $rKey;
                }

                if ($resolution == '1080')
                    $p1080 = true;
            }

            if (!$p1080 && $p720)
                $result[] = "[1080]{$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/720.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/720.mp4";
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

        // ads

        $preroll = null;
        $midroll = null;

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {

            $ads = [
                'preroll' => null,
                'midroll' => null,
                'postroll' => null
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
                $midroll[] = [
                    'time' => '90%',
                    'vast' => implode(' and ', $ads['postroll'])
                ];

        // }

        /*if (isset($_GET['debug'])) {
            $preroll = 'https://vastroll.ru/vast/vpaid.php?pl=10069&domain_ref=dle.testkholobok.ru';
        }*/
        
        /*if (isset($_GET['test'])) {
            $preroll = 'https://api.kholobok.biz/vast.xml?v=4.0';
        }*/

        $data['preroll'] = $preroll;
        $data['midroll'] = $midroll;

        // start

        // if ($this->request->domain != 'api.kholobok.biz' && $this->request->domain != 'kholobok.biz') {

            $dateNow = date("Y-m-d");

            $domainStats = Domain::select('show')->where('name', $this->request->domain)->first();

            if ($domainStats) {
                $stats = [];
                if ($domainStats->show != '') {
                    $stats = json_decode($domainStats->show, true);
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

                        Cookie::queue(Cookie::make('startuniq'.date('Ymd'), '1', 1440, '/;SameSite=None', null, true, false, false, 'none'));
                    }
                } else {
                    $stats[$dateNow] = array(
                        'start' => 1,
                        'startuniq' => 1,
                        'show' => 0,
                        'lowshow' => 0,
                        'showads' => 0,
                    );

                    Cookie::queue(Cookie::make('startuniq'.date('Ymd'), '1', 1440, '/;SameSite=None', null, true, false, false, 'none'));
                }
                $stats = json_encode($stats);
                Domain::where('name', $this->request->domain)->update(['show' => $stats ]);
            }

        // }

        return view('player', $data);
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


