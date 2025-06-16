<?php
namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\SystemMessage;


class system extends Controller{

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



    public function getMessage(){
        $data = SystemMessage::get()->toArray();
        return [ 'data' => $data, 'messages' => [] ];
    }



    public function addMessage(){

        $type = $this->request->input('type');
        if($type == ''){
            return ['response' => [],'messages' => [['tupe'=>'error', 'message'=>'Не указанн type']] ];
        }

        $text = $this->request->input('text');
        if($text == ''){
            return ['response' => [],'messages' => [['tupe'=>'error', 'message'=>'Не указанн text']] ];
        }

        $lastId = SystemMessage::create([
            'type' => $type,
            'text' => $text,
        ])->id;

        return [ 'data' => [], 'messages' => [['tupe'=>'success', 'message'=>'Сообщение добавлено']] ];
    }



    public function deleteMessage(){

        $id = $this->request->input('id');
        if($id == ''){
            return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указан id']] ];
        }

        SystemMessage::where('id', $id )->delete();

        return [ 'data' => [], 'messages' => [['tupe'=>'success', 'message'=>'Сообщение удаленоы']] ];
    }

    

}