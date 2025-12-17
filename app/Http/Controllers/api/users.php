<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Domain;

use App\Operation;

class users extends Controller{

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
    $response = [];
    $messages = [];

    $query = User::select([
        'users.id',
        'users.login',
        'users.status',
        'users.api_key',
        'users.email',
        'users.name',
        'users.surname',
        'users.contact_telegram',
        'rights.name as tupe',
        \DB::raw('COALESCE(SUM(user_transactions.amount), 0) as balance_cents')
      ])
      ->join('link_rights', 'users.id', '=', 'link_rights.id_user')
      ->join('rights', 'link_rights.id_rights', '=', 'rights.id')
      ->leftJoin('user_transactions', 'users.id', '=', 'user_transactions.user_id')
      ->where('users.id', '!=', $this->user['id'])
      ->groupBy('users.id', 'users.login', 'users.status', 'users.api_key', 'users.email', 'users.name', 'users.surname', 'users.contact_telegram', 'rights.name');

    $page = $this->request->input('page');

    if ($page == 'users') {
      $query = $query->where('link_rights.id_rights', 1)->where('users.status', '!=', '0');
    } else if ($page == 'kontrol') {
      $query = $query->where('link_rights.id_rights', '!=', 1)->where('users.status', '!=', '0');
    } else {
      $query = $query->where('users.status', '0');
    }

    $users = $query->get();

    foreach ($users as $user) {
      $response[] = [
        'id' => $user->id,
        'login' => $user->login,
        'status' => $user->status,
        'api_key' => $user->api_key,
        'email' => $user->email,
        'name' => $user->name,
        'surname' => $user->surname,
        'contact_telegram' => $user->contact_telegram,
        'tupe' => $user->tupe,
        'score' => number_format($user->balance_cents / 100, 2, '.', '')
      ];
    }

    return ['data' => $response, 'messages' => $messages];
  }


  public function info(){
    $response = [];
    $messages = [];
    $user = User::select('id', 'login', 'status', 'api_key', 'cent', 'domain_id')->where('id', $this->user['id'])->first();
    $response = $user->toArray();

    $balanceInCents = $user->getBalance();
    $response['score'] = number_format($balanceInCents / 100, 2, '.', '');

    $response['operation'] = Operation::where('id_user', $this->user['id'])->get()->toArray();
    if($response['domain_id'] != 0){
      $response['domain'] = Domain::where('id', $response['domain_id'])->first()->toArray()['name'];
    }/*else{
      $response['domain'] = 'api.kholobok.biz';
    }*/
    

    return ['data' => $response,'messages' => $messages];
  }

  public function infoId(){
    $response = [];
    $messages = [];

    $id = $this->request->input('id');
    if($id == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн id']];
    }

    $response = User::select('id', 'login', 'status', 'api_key', 'score', 'cent', 'domain_id')->where('id', $id)->first()->toArray();

    $response['operation'] = Operation::where('id_user', $id)->get()->toArray();

    return ['data' => $response,'messages' => $messages];
  }

  public function putCent(){
    $response = [];
    $messages = [];

    $cent = $this->request->input('cent');
    if($cent == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн password']];
    }
    User::where('id', $this->user['id'])->update([ 'cent' => $cent ]);
    $messages[] = ['tupe'=>'success', 'message'=>'Данные обновлены'];

    return ['data' => $response,'messages' => $messages];
  }

  public function putStatusCent(){
    $response = [];
    $messages = [];

    $id = $this->request->input('id');
    $idUser = $this->request->input('idUser');
    $status = $this->request->input('status');

    $summ = $this->request->input('summ');
    if($summ != ''){
      $user = User::where('id', $idUser)->first();
      User::where('id', $idUser)->update([ 'score' => $user->score - $summ ]);
    }

    Operation::where('id', $id)->update([ 'status' => $status ]);

    return ['data' => $response,'messages' => $messages];
  }


  public function putPasword(){

    $response = ['operation' => false];
    $messages = [];

    $password = $this->request->input('password');
    if($password == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн password']];
    }

    $oldpassword = $this->request->input('oldpassword');
    if($oldpassword == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн oldpassword']];
    }

    $user = User::select('id','login','password')->where('id', $this->user['id'])->first();

    if( !Hash::check($oldpassword, $user->password) ){
      return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Пароль не верен', 'code' => 2]]];
    }

    User::where('id', $this->user['id'])->update([ 'password' => Hash::make($password) ]);


    return ['data' => [],'messages' => $messages];
  }


  public function putStatus(){
    $response = [];
    $messages = [];
    $ids = $this->request->input('ids');
    if($ids == ''){
      return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указаны ids']]];
    }
    $status = $this->request->input('status');
    if($status === ''){
      return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указанн status']]];
    }
    User::whereIn('id', $ids)->update([ 'status' => $status ]);

    return ['data' => [],'messages' => [['tupe'=>'info', 'message'=>'Статус установлен']]];
  }

  public function selectDomain(){
    $response = [];
    $messages = [];
    $id = $this->request->input('id');
    if($id == ''){
      return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указан id']]];
    }
    User::where('id', $this->user['id'])->update([ 'domain_id' => $id ]);

    return ['data' => [],'messages' => [['tupe'=>'success', 'message'=>'Домен выбран']]];
  }



  public function add(){
    $messages = [];
    $response = [];

    $login = $this->request->input('login');
    if($login == ''){
        return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн login']];
    }

    $password = $this->request->input('password');
    if($password == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн password']];
    }

    $email = $this->request->input('email');
    if($email == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн email']];
    }

    $tupe = $this->request->input('tupe');
    if($tupe == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн tupe']];
    }

    $contact_telegram = $this->request->input('contact_telegram');
    if($contact_telegram == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан Telegram']];
    }

    $name = $this->request->input('name');
    $endname = $this->request->input('endname');

    $userDB = User::where('login', $login)->first();

    // Проверка наличия user в базе
    if(!isset($userDB)){
      // Создаем запись
      $lastId = User::create([
        'login' => $login,
        'email' => $email,
        'password' => Hash::make($password),

        'name' => $name,
        'surname' => $endname,
        'contact_telegram' => $contact_telegram,

        'cent' => '{"yandex":null,"qiwi":null,"card":null,"webMoney":null}',

        'status' => 2,
        'api_key' => md5(time().$login)

      ])->id;

      $right = Right::where('name', $tupe)->first()->toArray();
      LinkRight::create(['id_user' => $lastId, 'id_rights' => $right['id'] ]);

      $response = [ 'status' => true, 'data' => ['id' => $lastId, 'name' => $login] ];
      $messages[] = ['tupe'=>'info', 'message'=>'Пользователь создан'];
    }else{
      $messages[] = ['tupe'=>'error', 'message'=>'Логин уже зарегистрирован'];
      $response = [ 'status' => false ];
    }
    return ['data' => $response,'messages' => $messages];
  }


  public function update(){
    $messages = [];
    $response = [];


    $id = $this->request->input('id');
    if($id == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн id']];
    }

    $login = $this->request->input('login');
    if($login == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн login']];
    }

    $password = $this->request->input('password');

    $email = $this->request->input('email');
    if($email == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн email']];
    }

    $tupe = $this->request->input('tupe');
    if($tupe == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн domain']];
    }

    $contact_telegram = $this->request->input('contact_telegram');
    if($contact_telegram == ''){
      return ['data' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указан Telegram']];
    }

    $name = $this->request->input('name');
    $surname = $this->request->input('surname');

    $userDB = User::where('id', $id)->first();

    // Проверка наличия user в базе
    if(isset($userDB)){

      $updateUser = [
        'login' => $login,
        'email' => $email,
        'name' => $name,
        'surname' => $surname,
        'contact_telegram' => $contact_telegram
      ];

      if($password != ''){
        $updateUser['password'] = Hash::make($password);
      }

      $right = Right::where('name', $tupe)->first()->toArray();
      LinkRight::where('id_user', $id)->update( ['id_rights' => $right['id'] ]);

      User::where('id', $id)->update($updateUser);
      $messages[] = ['tupe'=>'info', 'message'=>'Пользователь обновлен'];

    }else{
      $messages[] = ['tupe'=>'error', 'message'=>'Пользователь не найден'];
      $response = [ 'status' => false ];
    }





    return ['data' => $response,'messages' => $messages];
  }



    


}