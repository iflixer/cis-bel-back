<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Right;
use App\LinkRight;
use App\Cdn;

class cdns extends Controller{

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

  public function get(){
    $messages = [];

    $cdns = Cdn::select('id', 'host', 'node_name', 'comment', 'weight', 'active', 'counter', 'weight_counter', 'tx', 'rx', 'tx5m', 'rx5m', 'last_report')
             ->orderBy('id', 'asc')
             ->get()
             ->toArray();

    foreach ($cdns as $key => $value) {
      $cdns[$key]['active_text'] = $value['active'] ? 'Активен' : 'Неактивен';
      $cdns[$key]['weight'] = intval($value['weight']);
    }

    return ['data' => $cdns, 'messages' => $messages];
  }

  public function update(){
    $response = [];
    $messages = [];

    $id = $this->request->input('id');
    if($id == ''){
      return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Не указан id']]];
    }

    $host = $this->request->input('host');
    if($host == ''){
      return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Не указан host']]];
    }

    $weight = $this->request->input('weight');
    if($weight === '' || !is_numeric($weight)){
      return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Не указан вес или указано неверное значение']]];
    }

    $active = $this->request->input('active');
    if($active === ''){
      return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Не указан статус активности']]];
    }

    $comment = $this->request->input('comment', '');

    $cdn = Cdn::where('id', $id)->first();
    if(!$cdn){
      return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'CDN не найден']]];
    }

    $updateData = [
      'host' => $host,
      'weight' => intval($weight),
      'active' => intval($active),
      'comment' => $comment
    ];

    Cdn::where('id', $id)->update($updateData);
    $messages[] = ['tupe'=>'success', 'message'=>'CDN обновлен'];

    return ['data' => $response, 'messages' => $messages];
  }
}