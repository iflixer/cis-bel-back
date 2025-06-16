<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\User;
use App\Right;
use App\LinkRight;

use App\Ad;
use App\Show;
use App\Seting;

use App\Article;

class articles extends Controller
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
            if($key != 'id'){  $this->user[$key] = $value; }
        }
    }


    public function get(){
        $response = [];
        $messages = [];

        $response = Article::select('id','title','body','created_at')->whereNotIn('id', [8, 9, 10, 11, 12, 13])->get()->toArray();

        return ['data' => $response,'messages' => $messages];
    }


    public function getId(){
        $response = [];
        $messages = [];

        $id = $this->request->input('id');

        $response = Article::where('id', $id)->first()->toArray();

        $template = view("articles.{$id}", [
            'token' => $this->user['api_key']
        ])->render();

        $response['body'] .= $template;

        return ['data' => $response,'messages' => $messages];
    }



    public function add(){
        $response = [];
        $messages = [];

        $title = $this->request->input('title');
        $body = $this->request->input('body');
        

        $lastId = Article::create([
            'title' => $title,
            'body' => $body
        ])->id;


        return ['data' => $response,'messages' => $messages];
    }

    public function update(){
        $response = [];
        $messages = [];

        $id = $this->request->input('id');
        $title = $this->request->input('title');
        $body = $this->request->input('body');
        

        Article::where('id', $id)->update([
            'title' => $title,
            'body' => $body
        ]);
        

        return ['data' => $response,'messages' => [['tupe'=>'success', 'message'=>'Статья обновлена']]];
    }



    public function delete(){
        $response = [];
        $messages = [];

        $ids = $this->request->input('ids');
        

        Article::whereIn('id', explode(',', $ids) )->delete();
        

        return ['data' => $response,'messages' => [['tupe'=>'success', 'message'=>'Статьи удалены']]];
    }

}