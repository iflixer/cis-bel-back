<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
use App\Screenshot;

use App\Helpers\Image;

use Mail;
use DB;


// include $_SERVER['DOCUMENT_ROOT'].'/resources/simple_html_dom.php';

use JonnyW\PhantomJs\Client;

class ApiController extends Controller
{

    public $request;
    protected $loginVDB; //  = 'kolobock'
    protected $passVDB; //  = '5HxL2P2Yw1yq'

    // protected $adress = 'https://api.kholobok.biz/show/';
    protected $cdnhub_api_domain;
    protected $cdnhub_player_domain;
    protected $cdnhub_img_resizer_domain;
    protected $usesApi = "App\Http\Controllers\api\\";

    public function __construct(Request $request){
        $this->request = $request;

        $this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
        $this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
		$this->cdnhub_api_domain = Seting::where('name', 'cdnhub_api_domain')->first()->toArray()['value'];
		$this->cdnhub_player_domain = Seting::where('name', 'cdnhub_player_domain')->first()->toArray()['value'];
		$this->cdnhub_img_resizer_domain = Seting::where('name', 'cdnhub_img_resizer_domain')->first()->toArray()['value'];
    }


    public function start($method){

        if(strpos($method, ".") !== false){
            $metodElements = explode(".", $method);
            $nameClass = $this->usesApi.$metodElements[0];

            // $nameClass fex App\Http\Controllers\api\shows
            // $metodElements fex array:2 [
            //   0 => "shows"
            //   1 => "show"
            // ]

            $tiket = app()->make($nameClass, ['request' => $this->request]);
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

    protected function search()
    {
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

        $searchParams = [
            'kinopoisk_id' => $this->request->input('kinopoisk_id'),
            'imdb_id' => $this->request->input('imdb_id'),
            'title' => $this->request->input('title'),
            'offset' => $this->request->input('offset'),
            'limit' => $this->request->input('limit'),
            'orderby' => $this->request->input('orderby'),
            'orderby_direction' => $this->request->input('orderby_direction'),
            'type' => $this->request->input('type'),
        ];

        $videoSearchService = new \App\Services\VideoSearchService(
            $this->cdnhub_api_domain,
            $this->cdnhub_player_domain,
            $this->cdnhub_img_resizer_domain
        );

        return $videoSearchService->search($searchParams);
    }

    protected function setContentType(&$videos, &$video, $key) {
        $is_serial = false;
        switch ($video['type']) {
            case 'movie':
                $videos[$key]['type'] = 'movie';
                $video['type'] = 'movie';     
                break;
            case 'episode':
                $videos[$key]['type'] = 'serial';
                $video['type'] = 'serial';   
                $is_serial = true;  
                break;
            case 'anime':
                $videos[$key]['type'] = 'anime';
                $video['type'] = 'anime';     
                break;
            case 'animeepisode':
                $videos[$key]['type'] = 'animeserial';
                $video['type'] = 'animeserial'; 
                $is_serial = true;    
                break;
            case 'showepisode':
                $videos[$key]['type'] = 'showserial';
                $video['type'] = 'showserial';   
                $is_serial = true;  
                break;
        }
        return $is_serial;
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

    public function genres()
    {
        $data = Genre::select('id', 'name')
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        $result = [];

        foreach ($data as $genre) {
            $result[] = [
                'id' => $genre['id'],
                'name' => $genre['name'],
            ];
        }

        return [
            'result' => $result
        ];
    }

    protected function updates()
    {
        $result = null;
        $force_rebuild = $this->request->input('force_rebuild');

        if (empty($force_rebuild)) {
            $result = \Cache::get('updates');
        }

        if ($result === null) {
            $videoUpdateService = new \App\Services\VideoUpdateService(
                $this->cdnhub_api_domain,
                $this->cdnhub_player_domain,
                $this->cdnhub_img_resizer_domain
            );

            $result = $videoUpdateService->getUpdates();

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
                    'poster_url' => $this->makeInternalImageURL('actors', $link['id_actor'], $actors[$link['id_actor']]['poster_url'])
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
                    'poster_url' => $this->makeInternalImageURL('directors', $link['id_director'], $directors[$link['id_director']]['poster_url'])
                ];
            }
        }

        $translationsData = [];
        $translationsRaw = File::select(
                'files.id_parent',
                'translations.id as id',
                'translations.title as title',
                'translations.tag as tag'
            )
            ->whereIn('files.id_parent', $idsVideo)
            ->join('translations', 'files.translation_id', '=', 'translations.id')
            ->groupBy('files.id_parent', 'files.translation_id')
            ->get();

        foreach ($translationsRaw as $item) {
            $translationsData[$item->id_parent][] = [
                'id' => $item->id,
                'title' => $item->title,
                'tag' => $item->tag
            ];
        }

        // Дополнения
        foreach ($video as $value){
            $element = $value->toArray();

            $element['quality'] = preg_replace("#(.*)\s[0-9]+#i", "\\1", $element['quality']);

            // $file = File::select('translation')->where('id_parent', $value->id)->first();
            // if( isset($file) ){ $element['translation'] = $file->translation; }

            // Use pre-loaded translations (batch loaded above to avoid N+1 query)
            if (isset($translationsData[$value->id]))
                $element['translations'] = $translationsData[$value->id];

            $element['adress'] = "https://cdn0.{$this->cdnhub_player_domain}/show/{$element['id']}"; // Ссылка

            if(array_key_exists($element['id'], $idsGenresInVideos)){ $element['genre'] = $idsGenresInVideos[$element['id']]; } // Жанры
            if(array_key_exists($element['id'], $idsCountrysInVideos)){ $element['country'] = $idsCountrysInVideos[$element['id']]; } // Страны
            if(array_key_exists($element['id'], $idsActorsInVideos)){ $element['actors'] = $idsActorsInVideos[$element['id']]; }
            if(array_key_exists($element['id'], $idsDirectorsInVideos)){ $element['directors'] = $idsDirectorsInVideos[$element['id']]; }
            
            // replace images with internal links
            if ($element['img']) $element['img'] = $this->makeInternalImageURL('videos', $element['id'], $element['img']);
            if ($element['backdrop']) $element['backdrop'] = $this->makeInternalImageURL('videos', $element['id'], $element['backdrop']);
            
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


    private function makeInternalImageURL($type, $id, $url) {
        return Image::makeInternalImageURL($this->cdnhub_img_resizer_domain, $type, $id, $url);
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
