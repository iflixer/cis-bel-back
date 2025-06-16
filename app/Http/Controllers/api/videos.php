<?php
namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Seting;

use App\File;
use App\Video;
use App\Translation;

use App\Country;
use App\Genre;
use App\Link_country;
use App\Link_genre;

use App\Domain;

class videos extends Controller{

    public $request;
    protected $user;

    public function __construct(Request $request){
        $this->request = $request;
        if( $request->input('account_key') != ''){
            $this->user = User::where('api_key', $request->input('account_key'))->first()->toArray();
        }else{
            $this->user = User::where('id', $request->userId)->first()->toArray();
        }

        $idRight = LinkRight::where('id_user', $this->user['id'] )->first();
        $right = Right::where('id', $idRight->id_rights )->first()->toArray();

        foreach ($right as $key => $value) {
            if($key != 'id'){
                $this->user[$key] = $value;
            }
        }
    }


    // @1 - массив
    // @2 - основная таблица
    // @3 - таблица связи
    // @4 - название колонки
    // @5 - id элемента
    // @6 - разделитель
    protected function parseDopElements($elements, $tableElement, $tableLink, $nameColumn, $id){
        // $elements = explode($bort ,$stringElements);
        

        $tableLink::where('id_video',$id)->delete();

        foreach ($elements as $element) {
            if($element != ''){
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
                $dataLinkTable = $tableLink::where('id_video',$id)->where($nameColumn,$lastIdTable)->get();

                if($dataLinkTable->isEmpty()){
                    $tableLink::create(['id_video' => $id, $nameColumn => $lastIdTable]);
                }
            }
        }
    }

    
    protected function parseKinopoisk($id){

        // dump($id);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($curl, CURLOPT_USERAGENT, 'DLE Module v1.1 for VideoDB https://cdnhub.pro');	
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        // curl_setopt($curl, CURLOPT_USERPWD, $this->loginVDB.':'.$this->passVDB);
        curl_setopt($curl, CURLOPT_URL, 'https://kinopoiskapiunofficial.tech/api/v2.1/films/'.$id);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            // 'X-API-KEY: 102a2177-a594-48af-98a3-fa1f51b5a215'
            'X-API-KEY: c2e7a6f2-82f1-4415-b9af-42f991a4d0d4'
        ));

        // dump(curl_exec($curl));

        return json_decode(curl_exec($curl));
    }

    protected function parseConverter($id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, 'http://92.63.110.224/api.php?id=' . $id);

        return json_decode(curl_exec($curl));
    }






    public function info(){

        $count_vdb = File::where('sids','VDB')->count();

        $countVideo = Video::where('kinopoisk', '!=', null)->count();
        $countDropVideo = Video::where('update_kino', 1)->count();

        return [ 'data' => [ 'count' => $count_vdb, 'countVideo' => $countVideo, 'countDropVideo' => $countDropVideo, 'kinoPoisk' => '' ], 'messages' => [] ];
    }


    public function addKinoPoisk(){
        $messages = [];
        $response = [];

        $limit = $this->request->input('limit');

        $video = Video::where('update_kino', null)->where('kinopoisk', '!=', null)->limit($limit)->get();

        // $response = $video;

        foreach ($video as $value) {

            if (!$value->kinopoisk)
                continue;

            $film = '';
            if($value->kinopoisk < 2000000) $film = $this->parseKinopoisk($value->kinopoisk);
            
            $response[] = [ 'id' => $value->id ];
            
            if( $film != '' ){
                $kinoPoisk = $film->data;

                // dump($kinoPoisk);

                // Разбираем и сохраняем строку с жанрами
                $this->parseDopElements( array_map( function($item){ return $item->genre; }, $kinoPoisk->genres), new Genre, new Link_genre, 'id_genre', $value->id);

                // Разбираем и сохраняем строку с странами
                $this->parseDopElements( array_map( function($item){ return $item->country; }, $kinoPoisk->countries), new Country, new Link_country, 'id_country', $value->id);

                // Обновление данных фильма
                Video::where('id',$value->id)->update([
                    'year' => $kinoPoisk->year, 
                    'description' => $kinoPoisk->description, 
                    'img' => $kinoPoisk->posterUrl, 
                    'update_kino' => 1,

                    'film_length' => $kinoPoisk->filmLength,
                    'slogan' => $kinoPoisk->slogan,
                    'rating_mpaa' => $kinoPoisk->ratingMpaa,
                    'rating_age_limits' => $kinoPoisk->ratingAgeLimits,
                    'premiere_ru' => $kinoPoisk->premiereRu,
                    'distributors' => $kinoPoisk->distributors,
                    'premiere_world' => $kinoPoisk->premiereWorld,
                    'premiere_digital' => $kinoPoisk->premiereDigital,
                    'premiere_world_country' => $kinoPoisk->premiereWorldCountry,
                    'premiere_dvd' => $kinoPoisk->premiereDvd,
                    'premiere_blu_ray' => $kinoPoisk->premiereBluRay,
                    'distributor_release' => $kinoPoisk->distributorRelease,

                    'facts' => json_encode($kinoPoisk->facts),
                    'seasons' => json_encode($kinoPoisk->seasons),
                ]); 

            }else{
                Video::where('id',$value->id)->update([ 'update_kino' => 1 ]);
            }
        }

        return ['data' => $response,'messages' => $messages];
    }



    public function dataFilm(){

        $messages = [];
        $response = [];

        $id = $this->request->input('id');
        $video = Video::where('id', $id)->first();

        if( isset($video) ){

            $name = $video->ru_name;

            $url = '';

            $filess = [];
            $sesons = [];
            $translations = [];

            $folder = '';
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $date = date('YmdH', strtotime("+1 days"));
            $susuritiKey = 'mB19SrYdROqY';

            $path = '';
            $playList = [];

            // $files = File::where('id_parent', $id)->get();
            $files = File::distinct()->select('files.*', 'translations.tag', 'translations.priority')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('priority', 'DESC')
                ->orderBy('season', 'ASC')
                ->orderBy('num', 'ASC')
                ->get();


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

            $domain = Domain::where('name', 'kholobok.biz')->first();

            $response = ['dataplayer' => $domain->new_player, 'playList' => $playList,];

            return ['data' => $response,'messages' => $messages];

        }
        abort(404);



    }

    public function translations()
    {
        $messages = [];
        $response = [];

        $query = $this->request->input('query');

        $translations = Translation::select('title as value', 'id');

        if ($query)
            $translations->where('title', 'like', "%{$query}%");

        $translations = $translations->orderBy('title', 'asc')
            ->get()
            ->toArray();

        return [
            'data' => $translations,
            'messages' => $messages
        ];
    }

    public function addVideo()
    {
        $data = $this->request->input('ad');

        if (!isset($data['type']) || !isset($data['quality']) || !isset($data['kinopoisk']) || !isset($data['converter']) || !isset($data['translationId'])) {
            $messages = [
                [
                    'tupe' => 'error',
                    'message' => 'Заполните все обязательные поля'
                ]
            ];

            return [
                'data' => ['error' => true],
                'messages' => $messages,
            ];
        }

        $conv = $this->parseConverter($data['converter']);

        if ($conv->status != 'success') {
            $messages = [
                [
                    'tupe' => 'error',
                    'message' => 'Не найден источник видео файла'
                ]
            ];

            return [
                'data' => ['error' => true],
                'messages' => $messages,
            ];
        }

        $lock = [];

        if (isset($data['lock'])) {
            
            if (isset($data['lock']['RU']) && $data['lock']['RU'])
                $lock[] = 'RU';

            if (isset($data['lock']['UA']) && $data['lock']['UA'])
                $lock[] = 'UA';

            if (isset($data['lock']['SNG']) && $data['lock']['SNG'])
                $lock[] = 'SNG';

        }

        $lock = implode(',', $lock);

        if (isset($data['lock']) && isset($data['lock']['FULL']) && $data['lock']['FULL'])
            $lock = 'FULL';

        $id = Video::create([
            'tupe' => $data['type'],
            'kinopoisk' => isset($data['kinopoisk']) && $data['kinopoisk'] ? $data['kinopoisk'] : null,
            'imdb' => isset($data['imdb']) && $data['imdb'] ? $data['imdb'] : null,
            'quality' => isset($data['quality']) && $data['quality'] ? $data['quality'] : null,
            'lock' => $lock ? $lock : null,
        ])->id;

        if ($data['kinopoisk']) {
            $film = $this->parseKinopoisk($data['kinopoisk']);

            if ($film != '') {
                $kinoPoisk = $film->data;

                // dump($kinoPoisk);

                // Разбираем и сохраняем строку с жанрами
                $this->parseDopElements( array_map( function($item){ return $item->genre; }, $kinoPoisk->genres), new Genre, new Link_genre, 'id_genre', $id);

                // Разбираем и сохраняем строку с странами
                $this->parseDopElements( array_map( function($item){ return $item->country; }, $kinoPoisk->countries), new Country, new Link_country, 'id_country', $id);

                // Обновление данных фильма
                Video::where('id', $id)->update([
                    'ru_name' => $kinoPoisk->nameRu,
                    'name' => $kinoPoisk->nameEn,

                    'year' => $kinoPoisk->year, 
                    'description' => $kinoPoisk->description, 
                    'img' => $kinoPoisk->posterUrl, 
                    'update_kino' => 1,

                    'film_length' => $kinoPoisk->filmLength,
                    'slogan' => $kinoPoisk->slogan,
                    'rating_mpaa' => $kinoPoisk->ratingMpaa,
                    'rating_age_limits' => $kinoPoisk->ratingAgeLimits,
                    'premiere_ru' => $kinoPoisk->premiereRu,
                    'distributors' => $kinoPoisk->distributors,
                    'premiere_world' => $kinoPoisk->premiereWorld,
                    'premiere_digital' => $kinoPoisk->premiereDigital,
                    'premiere_world_country' => $kinoPoisk->premiereWorldCountry,
                    'premiere_dvd' => $kinoPoisk->premiereDvd,
                    'premiere_blu_ray' => $kinoPoisk->premiereBluRay,
                    'distributor_release' => $kinoPoisk->distributorRelease,

                    'facts' => json_encode($kinoPoisk->facts),
                    'seasons' => json_encode($kinoPoisk->seasons),
                ]);

            } else {
                Video::where('id', $id)->update(['update_kino' => 1]);
            }
        }

        if ($conv->status == 'success') {
            if ($data['type'] == 'movie') {
                File::create([
                    'id_VDB' => $conv->id,
                    'id_parent' => $id,
                    'path' => $conv->file,
                    'season' => 0,
                    'resolutions' => $conv->resolutions,
                    'num' => 0,
                    'translation_id' => $data['translationId'],
                    'sids' => 'ZCDN'
                ]);
            }

            if ($data['type'] == 'episode') {
                File::create([
                    'id_VDB' => $conv->id,
                    'id_parent' => $id,
                    'path' => $conv->file,
                    'season' => $data['season'],
                    'resolutions' => $conv->resolutions,
                    'num' => $data['episode'],
                    'translation_id' => $data['translationId'],
                    'sids' => 'ZCDN'
                ]);
            }
        }

        $messages = [
            [
                'tupe' => 'success',
                'message' => 'Видео успешно добавлено'
            ]
        ];

        return [
            'data' => ['error' => false],
            'messages' => $messages,
        ];
    }

    public function voiceManage()
    {
        $id = $this->request->input('id');
        $type = $this->request->input('type');

        if ($type == 'movie') {
            $voices = File::select('files.id', 'translations.title as translation', 'files.sids as db')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('translations.priority', 'desc')
                ->get()
                ->toArray();
        }

        if ($type == 'episode') {
            $voices = File::select('files.id', 'files.season', 'files.num as episode', 'translations.title as translation', 'files.sids as db')
                ->where('id_parent', $id)
                ->join('translations', 'files.translation_id', '=', 'translations.id')
                ->orderBy('translations.priority', 'desc')
                ->orderBy('files.season', 'asc')
                ->orderBy('files.num', 'asc')
                ->get()
                ->toArray();
        }
        

        return [
            'data' => ['voices' => $voices],
            'messages' => [],
        ];
    }

    public function addFile()
    {
        $data = $this->request->input('ad');

        if (!isset($data['id']) || !isset($data['type']) || !isset($data['converter']) || !isset($data['translationId'])) {
            $messages = [
                [
                    'tupe' => 'error',
                    'message' => 'Заполните все обязательные поля'
                ]
            ];

            return [
                'data' => ['error' => true],
                'messages' => $messages,
            ];
        }

        $conv = $this->parseConverter($data['converter']);

        if ($conv->status == 'success') {
            if ($data['type'] == 'movie') {
                File::create([
                    'id_VDB' => $conv->id,
                    'id_parent' => $data['id'],
                    'path' => $conv->file,
                    'season' => 0,
                    'resolutions' => $conv->resolutions,
                    'num' => 0,
                    'translation_id' => $data['translationId'],
                    'sids' => 'ZCDN'
                ]);
            }

            if ($data['type'] == 'episode') {
                File::create([
                    'id_VDB' => $conv->id,
                    'id_parent' => $data['id'],
                    'path' => $conv->file,
                    'season' => $data['season'],
                    'resolutions' => $conv->resolutions,
                    'num' => $data['episode'],
                    'translation_id' => $data['translationId'],
                    'sids' => 'ZCDN'
                ]);
            }
        } else {
            $messages = [
                [
                    'tupe' => 'error',
                    'message' => 'Не найден источник видео файла'
                ]
            ];

            return [
                'data' => ['error' => true],
                'messages' => $messages,
            ];
        }

        $messages = [
            [
                'tupe' => 'success',
                'message' => 'Файл успешно добавлен'
            ]
        ];

        return [
            'data' => ['error' => false],
            'messages' => $messages,
        ];
    }

    public function deleteFile()
    {
        $id = $this->request->input('id');

        if ($id) {
            File::where('id', $id)->delete();
        }

        $messages = [
            [
                'tupe' => 'success',
                'message' => 'Файл успешно удален'
            ]
        ];

        return [
            'data' => [],
            'messages' => $messages,
        ];
    }

}