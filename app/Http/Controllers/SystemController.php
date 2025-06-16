<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\LinkRight;
use App\Right;
use Auth;


class SystemController extends Controller{


    protected $host; // Осоциативный массив политики доступа
    public $messages; // Массив оповещений
    public $name; // Название политики





    public function __construct(){
        $this->messages = [
            ['tupe'=>'info', 'message'=>'Система работает в тестовом режиме']//,
            //['tupe'=>'error', 'message'=>'Интерфейсом "ЗАГРУЗКА С VIDEODB" пользоваться запрещено!']
        ];
        $this->userActivatedAccount();


        // Получаем id политики для пользователя
        $link = LinkRight::where('id_user', Auth::id())->first();

        // Получаем тип пользователя
        $right = Right::where('id', $link->id_rights )->first();
        $tupeUser = $right->name;
        $this->name = $right->ru_name;

        // Получаем политику
        $politics = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/politics.json'), true);
        $this->host = $politics[$tupeUser]['tupe'];


        /*
        $variable = Right::where('id', $link->id_rights )->first();
        $this->name = $variable->name;
        // Декодируем, выбираем данные
        $this->host = json_decode($variable->tupe, true);
        */


    }


    protected function userActivatedAccount(){

        if(Auth::user()->status == 1){
            $this->messages[] = ['tupe'=>'error', 'message'=>'Подтвердите аккаунт', 'data' => [ 'tupe' => 'link', 'title' => 'Подтвердить', 'link' => 'api/activadedSet']];
        }
    }


    public function getPolitic($url){
        return $this->host[$url];
    }



    public function getMenu(){
        return json_encode($this->host);
    }

    public function getHeader($url){
        // Декодируем, выбираем данные по текущему url

        $data = [
            'url' => $this->host[$url]
        ];

        return json_encode($data);
    }








    //поиск и подгрузка компонентов
    public function components(){

        $dir = $_SERVER['DOCUMENT_ROOT'].'/resources/views/components';
        $sort = 0;

        $list = scandir($dir, $sort);
        if (!$list) return false;
        if ($sort == 0) unset($list[0],$list[1]);
        else unset($list[count($list)-1], $list[count($list)-1]);

        $files = $list;
    
        $rezultJs = '';
    
        foreach ($files as $value) {

            if(file_exists($dir.'/'.$value.'/'.$value.'.html')){
                $htmlFile = preg_replace("/'/m","\\'",
                    preg_replace('/\s+/m',' ',
                        preg_replace('/[\r\n]/m','',
                            file_get_contents($dir.'/'.$value.'/'.$value.'.html')
                        )
                    )
                );
            }else{
                echo "Warning: Фаил по адресу ".$dir.'/'.$value.'/'.$value.'.html не найден!';
                break;
            }

            if(file_exists($dir.'/'.$value.'/'.$value.'.js')){
                $jsFile = file_get_contents($dir.'/'.$value.'/'.$value.'.js');
            }else{
                echo "Warning: Фаил по адресу ".$dir.'/'.$value.'/'.$value.'.js не найден!';
                break;
            }


            $rezultJs .= preg_replace('/{{html}}/',$htmlFile ,$jsFile);

        }
        return "console.log('components load'); ".$rezultJs; // <script> </script>
    }

    
}
