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

class ads extends Controller
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


    public function upload(){
        $messages = [];
        $response = [];
        $file = $this->request->file('file');
        $file->move($_SERVER['DOCUMENT_ROOT'].'/public/img/ads/', $file->getClientOriginalName());
        return ['data' => $response,'messages' => $messages];
    }




    // Добавление объявления
    public function add(){

        $messages = [];
        $response = [];

        $ad = $this->request->input('ad');
        // dump($ad);

        Ad::create([
            'type' => $ad['type'],
            'body' => $ad['body'],
            'name' => $ad['name'],
            'sale' => $ad['sale'],
            'procent' => $ad['procent'],
            'position' => $ad['position'],
            'black_ad' => 0
        ]);

        return ['data' => $response,'messages' => $messages];
    }


    public function get(){
        $messages = [];
        $response = [];
        $response = Ad::get()->toArray();
        foreach ($response as $key => $value) {

            $shows = Show::selectRaw('SUM(shows)')->where('id_ad', $value['id'])->first()->toArray()['SUM(shows)'];
            isset($shows) ? $response[$key]['shows'] = $shows : $response[$key]['shows'] = 0;

            $showsNow = Show::select('shows')->where('id_ad', $value['id'])->whereDate('created_at', '=', Carbon::today())->first(); 
            isset($showsNow) ?  $response[$key]['showsNow'] = $showsNow->shows : $response[$key]['showsNow'] = 0;

            $response[$key]['on'] = $response[$key]['on'] > 0 ? true : false;
        }
        return ['data' => $response,'messages' => $messages];
    }

    public function on(){
        $messages = [];
        $response = [];

        $id = $this->request->input('id');
        $on = $this->request->input('on');
        Ad::where('id', $id)->update([
            'on' => $on ? 1 : 0
        ]);

        return ['data' => $response,'messages' => $messages];
    }


    public function update(){
        $response = [];
        $messages = [ ['tupe'=>'success', 'message'=>'Обновлено'] ];

        $ad = $this->request->input('ad');
        Ad::where('id', $ad['id'])->update([
            'type' => $ad['type'],
            'body' => $ad['body'],
            'name' => $ad['name'],
            'sale' => $ad['sale'],
            'procent' => $ad['procent'],
            'position' => $ad['position'],
            'black_ad' => $ad['black_ad']
        ]);

        return ['data' => $response,'messages' => $messages];
    }

    
    public function delete(){
        $response = [];
        $messages = [];

        $ids = $this->request->input('ids');
        if($ids == ''){
            return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указаны ids']] ];
        }

        Ad::whereIn('id', explode(',', $ids) )->delete();

        return ['data' => $response,'messages' => [['tupe'=>'info', 'message'=>'Объявления удалены']]];
    }



    public function getSetings(){
        $response = [];
        $messages = [];

        $setings = Seting::get()->toArray();

        foreach ($setings as $value) {
            $response[$value['name']] = $value['value'];
        }

        return ['data' => $response,'messages' => $messages];
    }


    public function saveSetings(){
        $response = [];
        $messages = []; 

        $setings = $this->request->input('setings');

        

        foreach ($setings as $key => $value) {
            Seting::where('name', $key)->update(['value' => $value]);
        }

        return ['data' => $response,'messages' => $messages];
    }




}