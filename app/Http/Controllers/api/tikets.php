<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Tiket;
use App\Message;

use App\Operation;

class tikets extends Controller
{

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


    // return ['response' => $response,'messages' => $messages];

    
    public function get(){
        $response = [];
        $messages = [];

        $queryTikets = Tiket::select()->orderBy('id', 'DESC');

        if( null !== $this->request->input('tupe') ){
            $tupe = $this->request->input('tupe');
            $queryTikets = $queryTikets->where('tupe', $tupe);
        }
        if( null !== $this->request->input('status') ){
            $status = $this->request->input('status');
            $queryTikets = $queryTikets->where('status', $status);
        }
        if( null !== $this->request->input('id_user') ){
            $id_user = $this->request->input('id_user');
            $queryTikets = $queryTikets->where('id_user', $id_user);
        }
        if($this->user['name'] == 'client'){
            $queryTikets = $queryTikets->where('id_user', $this->user['id']);
        }
        if( $this->request->input('close') == 'true' ){
            $queryTikets = $queryTikets->where('status', '4');
        }else{
            $queryTikets = $queryTikets->where('status', '!=','4');
        }

        $response = $queryTikets->get()->toArray();
        foreach ($response as $key => $value) {
            $message = Message::where('id_tiket', $value['id'])->orderBy('created_at', 'DESC')->first()->toArray();
            if($message['id_user'] == $this->user['id']){
                $message['name'] = "Вы";
            }else{

                $userMessage = User::where('id', $message['id_user'])->first();
                if($userMessage != null){
                    
                    $userMessage = $userMessage->toArray();
                    if($userMessage['name'] == '' && $userMessage['surname'] == ''){
                        $message['name'] = $userMessage['login'];
                    }else{
                        $message['name'] = $userMessage['name'].' '.$userMessage['surname'];
                    }
                }
            }
            $response[$key]['message'] = $message;
        }
        return ['data' => $response,'messages' => $messages];
    }


    public function getId(){
        $response = [];
        $messages = [];

        $id = $this->request->input('id');
        if($id == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн id']];
        }
        $response = Message::where('id_tiket', $id)->orderBy('created_at')->get()->toArray();
        foreach ($response as $key => $value) {
            if($value['id_user'] == $this->user['id']){
                $name = "Вы";
            }else{
                $userMessage = User::where('id', $value['id_user'])->first()->toArray();
                if($userMessage['name'] == '' && $userMessage['surname'] == ''){
                    $name = $userMessage['login'];
                }else{
                    $name = $userMessage['name'].' '.$userMessage['surname'];
                }
                
            }
            $response[$key]['name'] = $name;
        }
        return ['data' => $response,'messages' => $messages];
    }


    public function getNew(){
        $response = [];
        $messages = [];

        $queryMessage = Message::selectRaw('count(*) as count')->where('read', 0);
        if($this->user['name'] == 'client'){

            $queryTikets = Tiket::select()->orderBy('created_at', 'DESC')->where('id_user', $this->user['id'])->get()->toArray();
            $queryTikets = array_map(function($item){ return $item['id']; }, $queryTikets);
            $queryMessage = $queryMessage->whereIn('id_tiket', $queryTikets)->where('id_user', '!=', $this->user['id']);

        }else{
            $queryMessage = $queryMessage->whereRaw('id_user IN ( SELECT `id_user` FROM `link_rights` WHERE `id_rights` = 1 )');
        }

        $response = $queryMessage->first()->toArray();
        return ['data' => $response,'messages' => $messages];
    }

    public function read(){
        $response = [];
        $messages = [];
        $ids = $this->request->input('ids');
        if($ids == '') return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн ids']];
        Message::where('id_user', '!=', $this->user['id'])->whereIn('id', explode(',', $ids) )->update([ 'read' => 1 ]);
        return ['data' => $response,'messages' => $messages];
    }


    public function addCent(){
        $response = [];
        $messages = [ ['tupe'=>'success', 'message'=>'Заявка создана'] ];

        $title = $this->request->input('title');
        if($title == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан title']];
        }

        $cent = $this->request->input('cent');
        if($cent == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан кошелек для вывода']];
        }

        $idOperation = Operation::create([
            'id_user' => $this->user['id'],
            'summ' => $title,
            'cent' => json_decode($this->user['cent'], true)[$cent],
        ])->id;

        $data = [ 
            'id' => $idOperation,
            'summ' => $title, 
            'idUser' => $this->user['id'], 
            'loginUser' => $this->user['login'], 
            'cent' =>  $cent, 
            'score' => $this->user['score'],
            'dataCent' => json_decode($this->user['cent'], true)[$cent]
        ];

        $attachments = $this->request->input('attachments');
        

        $lastId = Tiket::create([
            'id_user' => $this->user['id'],
            'title' => 'Вывод средств на сумму '. $title,
            'tupe' => 'cent',
            'status' => '1',
            'data' => json_encode($data)
        ])->id;

        Message::create([
            'id_tiket' => $lastId,
            'id_user' => $this->user['id'],
            'message' => '',
            
            // 'attachments' => $attachments
        ]);

        return ['data' => $response,'messages' => $messages];
    }



    public function add(){

        $response = [];
        $messages = [];

        $tupe = $this->request->input('tupe');
        if($tupe == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан tupe']];
        }

        $title = $this->request->input('title');
        if($title == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан title']];
        }

        $message = $this->request->input('message');
        if($message == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан message']];
        }

        $data = $this->request->input('data');

        $attachments = $this->request->input('attachments');
        

        $lastId = Tiket::create([
            'id_user' => $this->user['id'],
            'title' => $title,
            'tupe' => $tupe,
            'status' => '1',
            'data' => $data
        ])->id;

        Message::create([
            'id_tiket' => $lastId,
            'id_user' => $this->user['id'],
            'message' => $message,
            
            // 'attachments' => $attachments
        ]);

        return ['data' => $response,'messages' => $messages];
    }




    public function addMessage(){

        $response = [];
        $messages = [];

        $id = $this->request->input('id');
        if($id == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн id']];
        }

        $message = $this->request->input('message');
        if($message == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн message']];
        }

        $attachments = $this->request->input('attachments');


        Message::create([
            'id_tiket' => $id,
            'id_user' => $this->user['id'],
            'message' => $message,
            // 'attachments' => $attachments
        ]);

        return ['data' => $response,'messages' => $messages];
    }



    public function statPut(){

        $response = [];
        $messages = [];


        $id = $this->request->input('id');
        if($id == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн id']];
        }

        $status = $this->request->input('status');
        if($status == ''){
            return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн status']];
        }


        Tiket::where('id', $id)->update([ 'status' => $status ]);


        return ['data' => $response,'messages' => $messages];
    }


    public function getUsers(){
        $response = [];
        $messages = [];

        if ($this->user['name'] == 'client' || $this->user['name'] == 'redactor') {
            return ['data' => $response, 'messages' => $messages];
        }

        $users = User::select('id', 'login', 'name', 'surname')
            ->orderBy('login', 'asc')
            ->get()
            ->toArray();

        foreach ($users as $user) {
            $displayName = trim($user['name'] . ' ' . $user['surname']);
            $response[] = [
                'id' => $user['id'],
                'label' => $displayName ?: $user['login']
            ];
        }

        return ['data' => $response, 'messages' => $messages];
    }


}
