<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;


use App\LinkRight;
use App\Right;
use App\User;
use App\Video;
use App\File;
use App\Domain;
use App\Seting;

use App\Translation;
use App\Videodb;

use App\Country;
use App\Genre;
use App\Link_country;
use App\Link_genre;
use App\Actor;
use App\Director;
use App\Link_actor;
use App\Link_director;

use Mail;
use DB;


// include $_SERVER['DOCUMENT_ROOT'].'/resources/simple_html_dom.php';

use JonnyW\PhantomJs\Client;

class ApiController extends Controller{

    public $request;
    protected $loginVDB; //  = 'kolobock'
    protected $passVDB; //  = '5HxL2P2Yw1yq'

    // protected $adress = 'https://api.kholobok.biz/show/';
    // protected $adress = 'https://cdn0.futemaxlive.com/show/';
    protected $adress = 'https://cdn0.futemaxlive.com/show/';

    protected $usesApi = "App\Http\Controllers\api\\";



    public function __construct(Request $request){
        $this->request = $request;

        $this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
        $this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
    }


    public function start($method){

        if(strpos($method, ".") !== false){
            $metodElements = explode(".", $method);
            $nameClass = $this->usesApi.$metodElements[0];


            $tiket = new $nameClass( $this->request );
            // return response()->json( $tiket->$metodElements[1]() );
            return response()->json(call_user_func([$tiket, $metodElements[1]]));
        }

        return  response()->json($this->$method());
    }







    /* Системные методы */

    protected function updateVideoDB(){

        

    }


    protected function addVideoDB(){
        
        $sync = false;
        $offset = 0;

        $steps = 0;
        $rezIds = [];
        $vdBrezIds = [];

        if ($this->request->input('sync') === true)
            $sync = true;

        if( null !== $this->request->input('offset') ){
            $offset = $this->request->input('offset');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($curl, CURLOPT_USERAGENT, 'DLE Module v1.1 for VideoDB https://cdnhub.pro');	
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_USERPWD, $this->loginVDB.':'.$this->passVDB);

        if ($sync)
            curl_setopt($curl, CURLOPT_URL, 'https://videodb.win/api/v1/medias?ordering=-created&limit=50&offset='.$offset); // curl_setopt($curl, CURLOPT_URL, 'https://videodb.win/api/v1/medias?ordering=-created&limit=50&offset='.$offset);
        else
            curl_setopt($curl, CURLOPT_URL, 'https://videodb.win/api/v1/medias?ordering=created&limit=50&offset='.$offset);

        $rezult = json_decode( curl_exec($curl) );

        curl_close($curl);


        // last updated at begin

        if ($sync) {
            if ($this->request->input('accepted_at') !== false)
                $last_accepted_at = intval($this->request->input('accepted_at'));
            else {
                $data = Videodb::select('last_accepted_at')->where('method', 'sync')->first()->toArray();
                $last_accepted_at = strtotime($data['last_accepted_at']);

                // set next checkpoint

                Videodb::where('method', 'sync')->update([
                    'last_accepted_at' => $rezult->results[0]->created
                ]);
            }
            
        }

        $stop_update = false;

        // last updated at end


        foreach ($rezult->results as $key => $value) {


            // check if end of update begin

            if ($sync) {
                if (!$stop_update && strtotime($value->created) < $last_accepted_at)
                    $stop_update = true;

                if ($stop_update)
                    continue;
            }

            // check if end of update end


            $resolution = '';
            $i = 0;

            foreach ($value->qualities as $resol) {
                if($i == 0){
                    $resolution = $resol->resolution;
                }else{
                   $resolution = $resolution.','.$resol->resolution;
                }
                $i++;
            }

            $translation = Translation::where('id_VDB', $value->translation->id)
                ->first();

            if (!$translation) {
                Translation::create([
                    'id_VDB' => $value->translation->id,
                    'title' => $value->translation->title
                ]);

                $translation = Translation::where('id_VDB', $value->translation->id)
                    ->first();
            }
            
            if($value->content_object->content_type == 'movie'){
                // 'movie'

                $video = Video::where('id_VDB', $value->content_object->id)->where('tupe', 'movie')->first();
                if( !isset($video) ){
                    $lastId = Video::create([
                        'id_VDB' => $value->content_object->id, 
                        'tupe' => $value->content_object->content_type,
                        'name' => $value->content_object->orig_title, 
                        'ru_name' => $value->content_object->ru_title, 
                        'kinopoisk' => $value->content_object->kinopoisk_id,
                        'imdb' => $value->content_object->imdb_id,
                        'quality' => $value->source_quality.' '.$value->max_quality,
                        'year' => '',
                        'country' => '', 
                        'description' => '',
                        'img' => ''
                    ])->id;
                }else{
                    $lastId = $video->id;

                    Video::where('id', $video->id)->
                        update([
                            'id_VDB' => $value->content_object->id, 
                            'tupe' => $value->content_object->content_type,
                            'name' => $value->content_object->orig_title, 
                            'ru_name' => $value->content_object->ru_title, 
                            'kinopoisk' => $value->content_object->kinopoisk_id,
                            'imdb' => $value->content_object->imdb_id,
                            'quality' => $value->source_quality.' '.$value->max_quality
                        ]);
                }

                $file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
                if( !isset($file) ){
                    $steps++;
                    $vdBrezIds[] = $value->id;
                    $rezIds[] = File::create([
                        'id_VDB' => $value->id,
                        'id_parent' => $lastId,
                        'path' => $value->path,
                        'name' => $value->content_object->orig_title,
                        'ru_name' => $value->content_object->ru_title,
                        'season' => 0,
                        'resolutions' => $resolution,
                        'num' => 0,
                        'translation_id' => $translation->id,
                        'translation' => $value->translation->title,
                        'sids' => 'VDB'
                    ])->id;
                }

            }

            if($value->content_object->content_type == 'episode'){
                // 'episode'

                $video = Video::where('id_VDB', $value->content_object->tv_series->id)->where('tupe', 'episode')->first();

                if( !isset($video) ){
                    $lastId = Video::create([
                        'id_VDB' => $value->content_object->tv_series->id, 
                        'tupe' => $value->content_object->content_type,
                        'name' => $value->content_object->tv_series->orig_title, 
                        'ru_name' => $value->content_object->tv_series->ru_title,
                        'kinopoisk' => $value->content_object->kinopoisk_id,
                        'imdb' => $value->content_object->imdb_id,
                        'quality' => $value->source_quality.' '.$value->max_quality,
                        'year' => '',
                        'country' => '', 
                        'description' => '', 
                        'img' => ''
                    ])->id;
                }else{
                    $lastId = $video->id;

                    Video::where('id', $video->id)->
                        update([
                            'id_VDB' => $value->content_object->tv_series->id, 
                            'tupe' => $value->content_object->content_type,
                            'name' => $value->content_object->tv_series->orig_title, 
                            'ru_name' => $value->content_object->tv_series->ru_title,
                            'kinopoisk' => $value->content_object->kinopoisk_id,
                            'imdb' => $value->content_object->imdb_id,
                            'quality' => $value->source_quality.' '.$value->max_quality
                        ]);
                }

                $file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
                if( !isset($file) ){
                    $steps++;
                    $vdBrezIds[] = $value->id;
                    $rezIds[] = File::create([
                        'id_VDB' => $value->id,
                        'id_parent' => $lastId,
                        'path' => $value->path,
                        'name' => $value->content_object->orig_title,
                        'ru_name' => $value->content_object->ru_title,
                        'season' => $value->content_object->season->num,
                        'resolutions' => $resolution,
                        'num' => $value->content_object->num,
                        'translation_id' => $translation->id,
                        'translation' => $value->translation->title,
                        'sids' => 'VDB'
                    ])->id;
                }

            }
        }

        if ($sync) {
            return  [
                'data' => [
                    'steps' => $steps,
                    'vdbIds' => $vdBrezIds,
                    'ids' => $rezIds,
                    'next' => $rezult->next,
                    'rezult' => $rezult,
                    'last_accepted_at' => $last_accepted_at,
                    'stop' => $stop_update
                ],
                'messages' => []
            ];
        } else {
            return [
                'data' => [
                    'steps' => $steps,
                    'vdbIds' => $vdBrezIds,
                    'ids' => $rezIds,
                    'next' => $rezult->next,
                    'rezult' => $rezult
                ],
                'messages' => []
            ];
        }
    }









    // отправка письма на ативацию аккаунта
    protected function activadedSet(){
        $key = $this->request->input('account_key');
        $user = User::where('api_key', $key)->first();
        Mail::send('emails.activation', ['login' => $user->login, 'email' => $user->key, 'key' => $key], function($message) use ($user){
            $message->to($user->email, $user->login)->subject('Активация аккаунта');
        });
    }








    // @1 - массив
    // @2 - основная таблица
    // @3 - таблица связи
    // @4 - название колонки
    // @5 - id элемента
    // @6 - разделитель
    protected function parseDopElements($stringElements, $tableElement, $tableLink, $nameColumn, $id, $bort){
        $elements = explode($bort ,$stringElements);

        $tableLink::where('id_video',$id)->delete();

        foreach ($elements as $element) {
            // Ищем запись, если нет - создаем
            $dataTable = $tableElement::where('name', $element)->first();
            if( !isset($dataTable->name) ){
                $lastIdTable = $tableElement::create([
                    'name' => $element
                ])->id;
            }else{
                $lastIdTable = $dataTable->id;
            }
            // Ищем связь, если нет - создаем
            //$dataLinkTable = $tableLink::where('id_video',$id)->where($nameColumn,$lastIdTable)->get();

            //if($dataLinkTable->isEmpty()){
                $tableLink::create(['id_video' => $id, $nameColumn => $lastIdTable]);
            //}
        }
    }


    /* api парсера */
    protected function parseConect(){
        if($this->request->isMethod('post')){// Получение данных
            $data = json_decode($this->request->input('data'), true);// Получение данных от парсера
            foreach ($data as $value) {
                if( count($value) > 1 ){
                    $this->parseDopElements($value['genres'], new Genre, new Link_genre, 'id_genre', $value['id'], ","); // Разбираем и сохраняем строку с жанрами
                    $this->parseDopElements($value['countrys'], new Country, new Link_country, 'id_country', $value['id'], ","); // Разбираем и сохраняем строку с странами
                    Video::where('id', $value['id'])->update(['year'=>$value['year'], 'description'=>$value['description'], 'img'=>$value['image'], 'update_kino' => 1]); // Обновление данных фильма
                }else{  Video::where('id', $value['id'])->update(['update_kino' => 1]); }
            }
            return ['count' => count($data),'messages' => 200];
        }elseif($this->request->isMethod('get')){// Выдача данных
            $offset = 0;
            $limit = 200;
            if( null !== $this->request->input('offset') ){ $offset = $this->request->input('offset'); }
            if( null !== $this->request->input('limit') && $this->request->input('limit') < $limit ){ $limit = $this->request->input('limit'); }
            $video = Video::select('id','kinopoisk')->whereNull('update_kino')->offset($offset)->limit($limit)->get();
            $count = Video::count();
            $data = [ "method" => "parseConect", "count" => $count, "items" => [] ];
            foreach ($video as $value){
                $data['items'][] = $value->toArray();
            }
            return $data;
        }
    }


    protected function updateVideo(){
        $element = json_decode($this->request->input('element'), true);

        if (isset($element['genre'])) {
            $this->parseDopElements($element['genre'], new Genre, new Link_genre, 'id_genre', $element['id'], ", "); // Разбираем и сохраняем строку с жанрами
        }

        if (isset($element['country'])) {
            $this->parseDopElements($element['country'], new Country, new Link_country, 'id_country', $element['id'], ", "); // Разбираем и сохраняем строку с странами
        }

        Video::where('id', $element['id'])->update([ 'ru_name' => $element['ru_name'], 'name' => $element['name'], 'year' => $element['year'], 'description' => $element['description'], 'img' => $element['img'],'lock' => $element['lock'] ]);
        
        return [ 'data' => [], 'messages' => [['tupe'=>'info', 'message'=>'Данные сохранены id - '.$element['id'] ]] ];
    }










    /* 
    | Публичные методы 
    | 
    */

    protected function search()
    {
        
        // filter

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

        $key = isset($_GET['account_key']) && $_GET['account_key'] ? $_GET['account_key'] : null;

        if (!$key)
            $key = isset($_GET['token']) && $_GET['token'] ? $_GET['token'] : null;

        $user = User::select('*')->where('api_key', $key)->get();

        $login = $user->isEmpty() ? $user[0]->login : 'undefined';

        if (
            $login == 'viewbox' && $_SERVER['HTTP_USER_AGENT'] == 'okhttp/4.9.0'
        ) {
            http_response_code(404);
            exit;
        }

        /*$serverInfo = var_export($serverInfo, true);
        $serverInfo = str_replace('array (', '', $serverInfo);
        $serverInfo = rtrim($serverInfo, ")\r");

        $key = isset($_GET['account_key']) && $_GET['account_key'] ? $_GET['account_key'] : null;

        if (!$key)
            $key = isset($_GET['token']) && $_GET['token'] ? $_GET['token'] : null;

        $user = User::select('*')->where('api_key', $key)->get();

        $serverInfoData = date('d.m.Y H:i:s') . (!$user->isEmpty() ? ' ' . $user[0]->login : ' undefined') . (isset($_GET['token']) ? ' ' . $_GET['token'] : ' undefined') . "\r{$serverInfo}\r\r";

        file_put_contents(__DIR__ . '/apiNetworkTraffic.log', $serverInfoData, LOCK_EX | FILE_APPEND);*/
        

        $offset = 0;
        $limit = 50;
        $kinopoisk_id = $this->request->input('kinopoisk_id');
        $imdb_id = $this->request->input('imdb_id');
        $title = $this->request->input('title');

        if ($this->request->input('offset') !== null)
            $offset = $this->request->input('offset');

        if ($this->request->input('limit') !== null)
            $limit = $this->request->input('limit');

        if ($limit > 200)
            $limit = 200;

        $videos = Video::select(
            'videos.id',
            'videos.created_at',
            'videos.tupe as type',
            'videos.name as title_orig',
            'videos.ru_name as title_rus',
            'videos.quality',
            'videos.year',
            'videos.kinopoisk as kinopoisk_id',
            'videos.imdb as imdb_id',
            'videos.description',
            'videos.img as poster',
            'videos.film_length as duration',
            'videos.slogan',
            'videos.rating_age_limits as age'
        );

        if ($kinopoisk_id) {
            $kinopoisk_id = explode(',', $kinopoisk_id);
            $videos->whereIn('videos.kinopoisk', $kinopoisk_id);
        } else {
            if ($imdb_id) {
                $imdb_id = explode(',', $imdb_id);
                $videos->whereIn('videos.imdb', $imdb_id);
            } else {
                if ($title) {
                    $videos->where('videos.ru_name', 'like', "%{$title}%")
                    ->orWhere('videos.name', 'like', "%{$title}%")
                    ->orderBy('videos.id', 'desc');
                } else
                    $videos->orderBy('videos.id', 'desc');
            }
        }

        $count = $videos->count();

        // $videos->rightJoin('files', 'videos.id', '=', 'files.id_parent')->groupBy('videos.id');
        $videos->leftJoin('files', 'videos.id', '=', 'files.id_parent')->groupBy('videos.id');

        // $videos = $videos->offset($offset)
        // ->limit($limit);

        $videos = $videos->offset($offset)
        ->limit($limit)
        ->get()
        ->toArray();

        // dd(vsprintf(str_replace('?', '%s', $videos->toSql()), $videos->getBindings()));

        if (!$videos)
            return [
                'result' => null
            ];

        // domain

        /*$idDomain = User::where('id', $this->request->userId)->first()->toArray()['domain_id'];

        if ($idDomain != 0)
            $domain = Domain::where('id', $idDomain)->first()->toArray()['name'];
        else
            $domain = 'api.kholobok.biz';*/

        $domain = 'cdn0.futemaxlive.com';

        // build data

        foreach ($videos as $key => $video) {
            if ($video['type'] !== 'movie') {
                $videos[$key]['type'] = 'serial';
                $video['type'] = 'serial';
            }

            // $videos[$key]['created'] = $video['created_at'];

            $videos[$key]['quality'] = explode(' ', $video['quality'])[0];

            $videos[$key]['iframe_url'] = "https://{$domain}/show/{$video['id']}";

            $genres = Link_genre::select('genres.name')->where('id_video', $video['id'])->join('genres', 'link_genres.id_genre', '=', 'genres.id')->get()->toArray();
            if ($genres) {
                foreach ($genres as $genre)
                    $videos[$key]['genres'][] = $genre['name'];
            }

            $countries = Link_country::select('countries.name')->where('id_video', $video['id'])->join('countries', 'link_countries.id_country', '=', 'countries.id')->get()->toArray();
            if ($countries) {
                foreach ($countries as $country)
                    $videos[$key]['countries'][] = $country['name'];
            }

            $actors = Link_actor::select('actors.name_ru', 'actors.name_en', 'actors.poster_url', 'link_actors.character_name')->where('id_video', $video['id'])->join('actors', 'link_actors.id_actor', '=', 'actors.id')->get()->toArray();
            if ($actors) {
                foreach ($actors as $actor)
                    $videos[$key]['actors'][] = [
                        'name_ru' => $actor['name_ru'],
                        'name_en' => $actor['name_en'],
                        'character_name' => $actor['character_name'],
                        'poster_url' => $actor['poster_url']
                    ];
            }

            $directors = Link_director::select('directors.name_ru', 'directors.name_en', 'directors.poster_url')->where('id_video', $video['id'])->join('directors', 'link_directors.id_director', '=', 'directors.id')->get()->toArray();
            if ($directors) {
                foreach ($directors as $director)
                    $videos[$key]['directors'][] = [
                        'name_ru' => $director['name_ru'],
                        'name_en' => $director['name_en'],
                        'poster_url' => $director['poster_url']
                    ];
            }

            if ($video['type'] == 'movie') {
                $translations = File::select('translations.id as id', 'translations.title as title', 'translations.tag as tag')
                ->where('id_parent', $video['id'])
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->groupBy('files.translation_id')
                ->get()
                ->toArray();

                if ($translations) {
                    foreach ($translations as $translation)
                        $videos[$key]['translations'][] = [
                            'id' => $translation['id'],
                            'title' => $translation['tag'] ?: $translation['title']
                        ];
                }
            }

            if ($video['type'] == 'serial') {
                $translations = File::select('translations.id as id', 'translations.title as title', 'translations.tag as tag')
                ->where('id_parent', $video['id'])
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->groupBy('files.translation_id')
                ->get()
                ->toArray();

                if ($translations) {
                    foreach ($translations as $translation) {
                        $last_season_episode = File::select('season', 'num as episode')
                        ->where('id_parent', $video['id'])
                        ->where('translation_id', $translation['id'])
                        ->orderBy('season', 'desc')
                        ->orderBy('num', 'desc')
                        ->first()
                        ->toArray();

                        $videos[$key]['translations'][] = [
                            'id' => $translation['id'],
                            'title' => $translation['tag'] ?: $translation['title'],
                            'season' => $last_season_episode['season'],
                            'episode' => $last_season_episode['episode'],
                        ];
                    }

                    $last_season_episode = File::select('season', 'num as episode')
                    ->where('id_parent', $video['id'])
                    ->orderBy('season', 'desc')
                    ->orderBy('num', 'desc')
                    ->first()
                    ->toArray();

                    $videos[$key]['season'] = $last_season_episode['season'];
                    $videos[$key]['episode'] = $last_season_episode['episode'];
                }

                /*$last_season_episode = File::select('season', 'num as episode')
                ->where('id_parent', $video['id'])
                ->orderBy('season', 'desc')
                ->orderBy('num', 'desc')
                ->first()
                ->toArray();

                $videos[$key]['season'] = $last_season_episode['season'];
                $videos[$key]['episode'] = $last_season_episode['episode'];*/
            }
        }

        // prev

        if ($offset >= $limit) {
            $prev = [
                'offset' => $offset - $limit,
                'limit' => $limit,
            ];

            if ($kinopoisk_id) {
                $prev['field'] = 'kinopoisk_id';
                $prev['value'] = $kinopoisk_id;
            }

            if ($imdb_id) {
                $prev['field'] = 'imdb_id';
                $prev['value'] = $imdb_id;
            }

            if ($title) {
                $prev['field'] = 'title';
                $prev['value'] = $title;
            }
        } else
            $prev = null;

        // next

        if ($count > $offset && ($offset + $limit) < $count) {
            $next = [
                'offset' => $offset + $limit,
                'limit' => $limit,
            ];

            if ($kinopoisk_id) {
                $next['field'] = 'kinopoisk_id';
                $next['value'] = $kinopoisk_id;
            }
            
            if ($imdb_id) {
                $next['field'] = 'imdb_id';
                $next['value'] = $imdb_id;
            }

            if ($title) {
                $next['field'] = 'title';
                $next['value'] = $title;
            }
        } else
            $next = null;

        // return

        return [
            'prev' => $prev,
            'result' => $videos,
            'next' => $next,
        ];
    }

    public function translations()
    {
        $data = Translation::select('id', 'title', 'tag')
        ->orderBy('id', 'asc')
        ->get()
        ->toArray();

        $result = [];

        foreach ($data as $translation) {
            $result[] = [
                'id' => $translation['id'],
                'title' => $translation['tag'] ?: $translation['title']
            ];
        }

        return [
            'result' => $result
        ];
    }

    protected function updates()
    {

        /*$data = Video::select('quality')
        ->groupBy('quality')
        ->get()
        ->toArray();

        $_data = [];

        foreach ($data as $item) {
            $_data[] = substr($item['quality'], 0, strpos($item['quality'], ' '));
        }

        $_data = array_unique($_data);*/

        $result = \Cache::get('updates');

        if ($result === null) {

            // domain

            /*$idDomain = User::where('id', $this->request->userId)->first()->toArray()['domain_id'];

            if ($idDomain != 0)
                $domain = Domain::where('id', $idDomain)->first()->toArray()['name'];
            else
                $domain = 'api.kholobok.biz';*/

            $domain = 'cdn0.futemaxlive.com';

            $result = [];

            $data = File::select('files.id', 'id_parent', 'created_at', 'season', 'num as episode', 'translations.id as t_id', 'translations.title as t_title', 'translations.tag as t_tag')
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                // ->whereRaw("created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))")
                ->whereRaw("season = 0 AND num = 0")
                // ->orderBy('files.created_at', 'desc')
                ->orderBy('files.id', 'desc')
                ->limit(300)
                ->get()
                ->toArray();

            $data = array_merge(

                $data,

                File::select('files.id', 'id_parent', 'created_at', 'season', 'num as episode', 'translations.id as t_id', 'translations.title as t_title', 'translations.tag as t_tag')
                    ->join('translations', 'files.translation_id', '=', 'translations.id')
                    // ->whereRaw("created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))")
                    ->whereRaw("season > 0 AND num > 0")
                    // ->orderBy('files.created_at', 'desc')
                    ->orderBy('files.id', 'desc')
                    ->limit(300)
                    ->get()
                    ->toArray()

            );

            foreach ($data as $item) {

                $_data = [];

                $_data['update_id'] = $item['id'];
                $_data['created'] = $item['created_at'];

                $_data['translation'] = [
                    'id' => $item['t_id'],
                    'title' => $item['t_tag'] ?: $item['t_title'],
                ];

                if ($item['season'] && $item['episode']) {
                    $_data['type'] = 'episode';
                    $_data['season'] = $item['season'];
                    $_data['episode'] = $item['episode'];
                } else
                    $_data['type'] = 'movie';

                // build content data

                $video = Video::select(
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
                    'film_length as duration',
                    'slogan',
                    'rating_age_limits as age'
                )
                ->where('id', $item['id_parent'])
                ->first()
                ->toArray();

                // build data

                $_data['content']['id'] = $video['id'] ?: null;

                if ($video['type'] !== 'movie') {
                    $_data['content']['type'] = 'serial';
                    $video['type'] = 'serial';
                }

                $_data['content']['title_orig'] = $video['title_orig'] ?: null;
                $_data['content']['title_rus'] = $video['title_rus'] ?: null;
                $_data['content']['year'] = $video['year'] ?: null;
                $_data['content']['description'] = $video['description'] ?: null;
                $_data['content']['poster'] = $video['poster'] ?: null;
                $_data['content']['duration'] = $video['duration'] ?: null;
                $_data['content']['slogan'] = $video['slogan'] ?: null;
                $_data['content']['age'] = $video['age'] ?: null;
                $_data['content']['kinopoisk_id'] = $video['kinopoisk_id'] ?: null;
                $_data['content']['imdb_id'] = $video['imdb_id'] ?: null;

                $_data['content']['quality'] = explode(' ', $video['quality'])[0];

                $_data['content']['iframe_url'] = "https://{$domain}/show/{$video['id']}";

                $genres = Link_genre::select('genres.name')->where('id_video', $video['id'])->join('genres', 'link_genres.id_genre', '=', 'genres.id')->get()->toArray();
                if ($genres) {
                    foreach ($genres as $genre)
                        $_data['content']['genres'][] = $genre['name'];
                }

                $countries = Link_country::select('countries.name')->where('id_video', $video['id'])->join('countries', 'link_countries.id_country', '=', 'countries.id')->get()->toArray();
                if ($countries) {
                    foreach ($countries as $country)
                        $_data['content']['countries'][] = $country['name'];
                }

                $actors = Link_actor::select('actors.name_ru', 'actors.name_en', 'actors.poster_url', 'link_actors.character_name')->where('id_video', $video['id'])->join('actors', 'link_actors.id_actor', '=', 'actors.id')->get()->toArray();
                if ($actors) {
                    foreach ($actors as $actor)
                        $_data['content']['actors'][] = [
                            'name_ru' => $actor['name_ru'],
                            'name_en' => $actor['name_en'],
                            'character_name' => $actor['character_name'],
                            'poster_url' => $actor['poster_url']
                        ];
                }

                $directors = Link_director::select('directors.name_ru', 'directors.name_en', 'directors.poster_url')->where('id_video', $video['id'])->join('directors', 'link_directors.id_director', '=', 'directors.id')->get()->toArray();
                if ($directors) {
                    foreach ($directors as $director)
                        $_data['content']['directors'][] = [
                            'name_ru' => $director['name_ru'],
                            'name_en' => $director['name_en'],
                            'poster_url' => $director['poster_url']
                        ];
                }

                if ($video['type'] == 'movie') {
                    $translations = File::select('translations.id as id', 'translations.title as title', 'translations.tag as tag')
                    ->where('id_parent', $video['id'])
                    ->join('translations', 'files.translation_id', '=', 'translations.id')
                    ->orderBy('priority', 'desc')
                    ->groupBy('files.translation_id')
                    ->get()
                    ->toArray();

                    if ($translations) {
                        foreach ($translations as $translation)
                            $_data['content']['translations'][] = [
                                'id' => $translation['id'],
                                'title' => $translation['tag'] ?: $translation['title']
                            ];
                    }

                    // result

                    $result['movies'][] = $_data;
                }

                if ($video['type'] == 'serial') {
                    $translations = File::select('translations.id as id', 'translations.title as title', 'translations.tag as tag')
                    ->where('id_parent', $video['id'])
                    ->join('translations', 'files.translation_id', '=', 'translations.id')
                    ->orderBy('priority', 'desc')
                    ->groupBy('files.translation_id')
                    ->get()
                    ->toArray();

                    if ($translations) {
                        foreach ($translations as $translation) {
                            $last_season_episode = File::select('season', 'num as episode')
                            ->where('id_parent', $video['id'])
                            ->where('translation_id', $translation['id'])
                            ->orderBy('season', 'desc')
                            ->orderBy('num', 'desc')
                            ->first()
                            ->toArray();

                            $_data['content']['translations'][] = [
                                'id' => $translation['id'],
                                'title' => $translation['tag'] ?: $translation['title'],
                                'season' => $last_season_episode['season'],
                                'episode' => $last_season_episode['episode'],
                            ];
                        }
                    }

                    $last_season_episode = File::select('season', 'num as episode')
                    ->where('id_parent', $video['id'])
                    ->orderBy('season', 'desc')
                    ->orderBy('num', 'desc')
                    ->first()
                    ->toArray();

                    $_data['content']['season'] = $last_season_episode['season'];
                    $_data['content']['episode'] = $last_season_episode['episode'];

                    // result

                    $result['serials'][] = $_data;
                }

            }

            \Cache::put('updates', $result, 3600);
        }

        return [
            'result' => $result
        ];

    }

    protected function kpids()
    {
        $type = $this->request->input('type');

        $result = [
            'status' => 'success',
            'data' => [],
        ];

        if ($type == 'movies') {
            $data = Video::select('kinopoisk')
            ->distinct()
            ->where('tupe', 'movie')
            ->get()
            ->toArray();

            if ($data) {
                foreach ($data as $item) {
                    if (trim($item['kinopoisk']))
                        $result['data'][] = intval(trim($item['kinopoisk']));
                }
            }
        }

        if ($type == 'serials') {
            $data = Video::select('kinopoisk')
            ->distinct()
            ->where('tupe', 'episode')
            ->get()
            ->toArray();

            if ($data) {
                foreach ($data as $item) {
                    if (trim($item['kinopoisk']))
                        $result['data'][] = intval(trim($item['kinopoisk']));
                }
            }
        }

        return $result;
    }

    /* Данные видео базы */
    protected function getVideo(){

        DB::listen(function ($query) {
            // dump([$query->sql, $query->time]);
            // $query->bindings
        });
        
        function getLincTable($idsVideo, $lincTableDB, $tableDB, $columnName){
            $lincTable = $lincTableDB::select($columnName,'id_video')->whereIn('id_video', $idsVideo)->get()->toArray();
            $idsTable = [];
            $idsTableInVideos = [];
            foreach ($lincTable as $value) {
                $idsTable[] = $value[$columnName];
                $idsTableInVideos[$value['id_video']][] = $value[$columnName];
            }
            $idsTable = array_unique($idsTable);
            $table = $tableDB::select('name', 'id')->whereIn('id', $idsTable)->pluck('name', 'id');
            foreach ($idsTableInVideos as $key => $value) {
                $idsTableInVideo = $value;
                foreach ($idsTableInVideo as $keyRez => $idTable) {
                    $idsTableInVideo[$keyRez] = $table[$idTable];
                }
                $idsTableInVideos[$key] = implode(', ', $idsTableInVideo);
            }
            return $idsTableInVideos;
        }

        function getIdsElements($string, $nameColumn){
            if($nameColumn == 'id_genre'){
                $tableElement = new Genre;
                $tableLink = new Link_genre;
            }
            if($nameColumn == 'id_country'){
                $tableElement = new Country;
                $tableLink = new Link_country;
            }
            $dataElements = explode(',', $string);
            $dataElements = $tableElement::select('id')->whereIn('name', $dataElements)->get()->toArray();
            $dataElements = array_map( function($e){return $e['id']; }, $dataElements);
            if($nameColumn == 'id_genre'){
                $dataElements = $tableLink::select('id_video')->whereIn($nameColumn, $dataElements)->groupBy('id_video')->havingRaw('count(*) = '.count($dataElements))->get()->toArray();
            }else{
                $dataElements = $tableLink::select('id_video')->whereIn($nameColumn, $dataElements)->get()->toArray();
            }
            $dataElements = array_map( function($e){return $e['id_video']; }, $dataElements);
            return $dataElements;
        }

        // Базовые значения
        $offset = 0;
        $limit = 200;
        $search = $this->request->input('search');
        $countries = $this->request->input('countries');
        $genres = $this->request->input('genres');
        $years = $this->request->input('years');

        $type = $this->request->input('type');
        $lock = $this->request->input('lock');

        $kinoPoisk = $this->request->input('kino_poisk');

        // Если есть offset
        if( null !== $this->request->input('offset') ){
            $offset = $this->request->input('offset');
        }
        // Если есть limit, но не более базового значения
        if( null !== $this->request->input('limit') && $this->request->input('limit') < $limit ){
            $limit = $this->request->input('limit');
        }

        $queryVideo = Video::select('id'); // Начало запроса

        if($countries != ''){ // Выборка по странам
            $queryVideo = $queryVideo->whereIn('id', getIdsElements($countries, 'id_country') );
        }
        if($genres != ''){ // Выборка по жанрам
            $queryVideo = $queryVideo->whereIn('id', getIdsElements($genres, 'id_genre') );
        }
        if($years != ''){ // Выборка по годам
            $queryVideo = $queryVideo->whereBetween('year', explode(",", $years));  
        }
        if($search != ''){ // Строка поиска
            $queryVideo = $queryVideo->where('ru_name', 'like', '%'.$search.'%')->orWhere('kinopoisk', 'like', '%'.$search.'%');
        }
        if($kinoPoisk != ''){ // ID кинопоиска
            $queryVideo = $queryVideo->whereIn('kinopoisk', array_slice(explode(',', $kinoPoisk), 0, 10));
        }

        if($lock != ''){ 
            if($lock == 'yes') $queryVideo = $queryVideo->where('lock', '!=', null);
        }
        if($type != ''){ 
            $queryVideo = $queryVideo->where('tupe', $type);
        }

        $count = $queryVideo->count();
        
        $timeCount = microtime(false);

        $idsVideo = array_map( function($item){ return $item['id']; }, $queryVideo->orderBy('id', 'ASC')->offset($offset)->limit($limit)->get()->toArray() );

        $timeIds = microtime(false);
        
        $video = Video::select()->whereIn('id', $idsVideo)->get();
        $timeVideos = microtime(false);



        /*$idDomain = User::where('id', $this->request->userId)->first()->toArray()['domain_id'];
        if($idDomain != 0){
            $domain = Domain::where('id', $idDomain)->first()->toArray()['name'];
        }else{
            $domain = 'api.kholobok.biz';
        }*/

        $domain = 'cdn0.futemaxlive.com';

        $timeDomains = microtime(false);
        
        
        // Данные для ответа
        $data = [
            
            
            "method" => "getVideo",
            "count" => $count,
            "items" => [],
            "genres" => Genre::get()->toArray(),
            "countries" => Country::get()->toArray(),
            "messages" => [],
        ];

        $idsGenresInVideos = getLincTable($idsVideo, new Link_genre, new Genre, 'id_genre');
        $idsCountrysInVideos = getLincTable($idsVideo, new Link_country, new Country, 'id_country');

        $idsActorsInVideos = [];
        $idsDirectorsInVideos = [];
        
        $actorLinks = Link_actor::select('id_actor', 'id_video', 'character_name')->whereIn('id_video', $idsVideo)->get()->toArray();
        $actorIds = array_unique(array_column($actorLinks, 'id_actor'));
        $actors = Actor::select('id', 'name_ru', 'name_en', 'poster_url')->whereIn('id', $actorIds)->get()->keyBy('id')->toArray();
        
        foreach ($actorLinks as $link) {
            if (isset($actors[$link['id_actor']])) {
                $idsActorsInVideos[$link['id_video']][] = [
                    'name_ru' => $actors[$link['id_actor']]['name_ru'],
                    'name_en' => $actors[$link['id_actor']]['name_en'],
                    'character_name' => $link['character_name'],
                    'poster_url' => $actors[$link['id_actor']]['poster_url']
                ];
            }
        }
        
        $directorLinks = Link_director::select('id_director', 'id_video')->whereIn('id_video', $idsVideo)->get()->toArray();
        $directorIds = array_unique(array_column($directorLinks, 'id_director'));
        $directors = Director::select('id', 'name_ru', 'name_en', 'poster_url')->whereIn('id', $directorIds)->get()->keyBy('id')->toArray();
        
        foreach ($directorLinks as $link) {
            if (isset($directors[$link['id_director']])) {
                $idsDirectorsInVideos[$link['id_video']][] = [
                    'name_ru' => $directors[$link['id_director']]['name_ru'],
                    'name_en' => $directors[$link['id_director']]['name_en'],
                    'poster_url' => $directors[$link['id_director']]['poster_url']
                ];
            }
        }

        

        // Дополнения
        foreach ($video as $value){
            $element = $value->toArray();

            $element['quality'] = preg_replace("#(.*)\s[0-9]+#i", "\\1", $element['quality']);

            // $file = File::select('translation')->where('id_parent', $value->id)->first();
            // if( isset($file) ){ $element['translation'] = $file->translation; }

            $files = File::select('translations.id as id', 'translations.title as title', 'translations.tag as tag')
                ->where('id_parent', $value->id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'desc')
                ->groupBy('files.translation_id')
                ->get()
                ->toArray();

            if ($files)
                $element['translations'] = $files;

            $element['adress'] = 'https://'.$domain.'/show/'.$element['id']; // Ссылка

            if(array_key_exists($element['id'], $idsGenresInVideos)){ $element['genre'] = $idsGenresInVideos[$element['id']]; } // Жанры
            if(array_key_exists($element['id'], $idsCountrysInVideos)){ $element['country'] = $idsCountrysInVideos[$element['id']]; } // Страны
            if(array_key_exists($element['id'], $idsActorsInVideos)){ $element['actors'] = $idsActorsInVideos[$element['id']]; }
            if(array_key_exists($element['id'], $idsDirectorsInVideos)){ $element['directors'] = $idsDirectorsInVideos[$element['id']]; }

            
            $data['items'][] = $element;

            // $element['adress'] = $this->adress.$element['id'];
        }

        $timeEnd = microtime(false);



        



        // $data["timeStart"] = $timeStart;
        // $data["timeCount1"] = $timeCount;
        // $data["timeIds2"] = $timeIds;
        $data["timeVideos3"] = $timeVideos;
        $data["timeDomains4"] = $timeDomains;
        $data["timeEnd"] = $timeEnd;

        // названия полей $model->getFillable()
        // массив данных $model->jsonSerialize()
        $messages = [];

        return ['data' => $data,'messages' => $messages];




        // $video = $queryVideo->offset($offset)->limit($limit)->get();
        // $sqlVideo = $queryVideo->orderBy('id', 'ASC')->offset($offset)->limit($limit); //->toSql();
        // $video = Video::select()->join($sqlVideo , function($join){ $join->on('b.id', '=', 'videos.id'); });

        // $video = [];

        // SELECT * FROM test_table ORDER BY id LIMIT 100000, 30
        // SELECT * FROM test_table JOIN (SELECT id FROM test_table ORDER BY id LIMIT 100000, 30) as b ON b.id = test_table.id

        // "select * from `videos` inner join (select * from `videos` limit 20 offset 56780) as b on `b`.`id` = `videos`.`id`"
        // "select * from `videos` inner join (select `id` from `videos` order by `id` asc limit 20 offset 56780) as b on `b`.`id` = `videos`.`id`"

        // $lastComment = Comment::select('created_at')
        //     ->whereColumn('user_id', 'users.id')
        //     ->latest()
        //     ->limit(1)
        //     ->getQuery();

        // $users = User::select('users.*')
        //     ->selectSub($lastComment, 'last_comment_at')
        //     ->get();

        // $query = Person::leftJoin('actions', function($q) use ($user)
        // {
        //     $q->on('actions.person_id', 'persons.id')
        //         ->where('actions.user_id', $user);
        // })
        // ->groupBy('persons.id')
        // ->where('type', 'foo')
        // ->where('actions.user_id', '=', $user)
        // ->get(['persons.id', 'full_name', DB::raw('count(actions.id) as total')]);
        
        // dump($testData);
        
    }







    


    public function show($method){
        $domain = $this->request->input('domain');
        $dateNow = date("Y-m-d");

        if($method == "show"){
            $domainStats = Domain::select('show')->where('name', $domain)->first();
            $stats = [];
            if($domainStats->show != ''){
                $stats = json_decode($domainStats->show, true);
            }
            if( isset($stats[$dateNow]) ){
                $stats[$dateNow]['show'] += 1;
                $stats[$dateNow]['lowshow'] += 1;
            }else{
                $stats[$dateNow]['show'] = 1;
                $stats[$dateNow]['lowshow'] = 1;
            }
            $stats = json_encode($stats);
            Domain::where('name', $domain)->update(['show' => $stats ]);
        }

        if($method == "fullshow"){
            $domainStats = Domain::select('show')->where('name', $domain)->first();
            $stats = json_decode($domainStats->show, true);
            $stats[$dateNow]['lowshow'] -= 1;
            $stats = json_encode($stats);
            Domain::where('name', $domain)->update(['show' => $stats ]);
        }

        if($method == "start"){
            return response()->json( $this->request->inDomain ); 
        }

        return; //response()->json($this->$method());
    }













}
