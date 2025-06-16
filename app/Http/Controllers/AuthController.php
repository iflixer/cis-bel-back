<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Http\Requests;

use Mail;

use App\User;
use App\Right;
use App\LinkRight;
use App\PasswordReset;

class AuthController extends Controller
{

    public $request;
    protected $bearer_token_secret = "bearer_token";
    protected $refresh_token_secret = "refresh_token";
    protected $time_token_set = 600000;


    public function __construct(Request $request){
        $this->request = $request;
    }
    
    
    public function registr(){

        $response = ['data' => ['operation' => false], 'messages' => []];


        $login = $this->request->input('login');
        $password = $this->request->input('password');
        $email = $this->request->input('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Введён не корректный Email адрес', 'code' => 0];
        }

        $userDB = User::where('login', $login)->first();
        if(isset($userDB)){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Пользователь с таким Логином уже зарегистрирован', 'code' => 0];
        }

        $userDB = User::where('email', $email)->first();
        if(isset($userDB)){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Пользователь с таким Email адресом уже зарегистрирован', 'code' => 0];
        }

        if( count($response['messages']) > 0 ){ return response()->json($response); }


        $bearer_token = bcrypt($login.$email.$this->bearer_token_secret);
        $refresh_token = bcrypt($login.$email.$this->refresh_token_secret);
        $time_token = time() + $this->time_token_set;

        // $idRight = LinkRight::where('id_user', $userDB->id )->first();
        $right = Right::where('id', 1)->first();

        $userId = User::create([
            'api_key' => md5(time().$login),
            'login' => $login,
            'email' => $email,
            'password' => bcrypt($password),
            'status' => 1,

            'cent' => '{"yandex":null,"qiwi":null,"card":null,"webMoney":null}',

            'bearer_token' => $bearer_token,
            'refresh_token' => $refresh_token,
            'time_token' => $time_token
        ])->id;
        LinkRight::create(['id_user' => $userId, 'id_rights' => 1])->id;

        $response['data'] = ['bearer_token' => $bearer_token, 'refresh_token' => $refresh_token, 'time_token' => $time_token, 'right' => $right->name, 'name' => $right->ru_name]; // 
        

        return response()->json($response);
    }



    public function forgotPassword()
    {
        $response = ['data' => ['operation' => false], 'messages' => []];

        $email = $this->request->input('email');

        $user = User::where('email', $email)->first();

        if (!isset($user)) {
            $response['messages'][] = [
                'tupe' => 'error',
                'message' => 'Пользователь с таким E-mail не существует',
                'code' => 0
            ];
        }

        if (count($response['messages']) > 0) {
            return response()->json($response);
        }

        $passwordReset = PasswordReset::where('email', $email)->first();

        if (isset($passwordReset)) {
            PasswordReset::where('email', $email)->delete();
        }

        $token = str_random(60);

        PasswordReset::create([
            'email' => $email,
            'token' => $token
        ]);

        $data = [
            'url' => "https://cdnhub.pro/reset-password?email={$email}&token={$token}"
        ];

        Mail::send('emails.forgot-password', $data, function($message) use ($email) {
            $message->to($email)->subject('Восстановление пароля');
        });

        $response['messages'][] = [
            'tupe' => 'success',
            'message' => 'Ссылка для восстановления пароля отправлена вам на почту',
            'code' => 0
        ];

        return response()->json($response);
    }

    public function resetPassword()
    {
        $response = ['data' => ['operation' => true], 'messages' => []];

        $email = $this->request->input('email');
        $token = $this->request->input('token');
        $password = $this->request->input('password');

        $passwordReset = PasswordReset::where('email', $email)->where('token', $token)->first();

        if (!isset($passwordReset)) {
            $response['messages'][] = [
                'tupe' => 'error',
                'message' => 'Не верный адрес',
                'code' => 0
            ];
        }

        if (count($response['messages']) > 0) {
            return response()->json($response);
        }

        User::where('email', $email)->update([
            'password' => bcrypt($password)
        ]);

        PasswordReset::where('email', $email)->where('token', $token)->delete();

        $response['messages'][] = [
            'tupe' => 'success',
            'message' => 'Новый пароль успешно установлен',
            'code' => 0
        ];

        return response()->json($response);
    }



    public function login(){

        $response = ['data' => ['operation' => false], 'messages' => []];


        $login = $this->request->input('login');
        $password = $this->request->input('password');


        $userDB = User::where('login', $login)->first();
        if(!isset($userDB)){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Пользователь не найден', 'code' => 1];
            return response()->json($response);
        }

        if( !Hash::check($password, $userDB->password) ){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Пароль не верен', 'code' => 2];
            return response()->json($response);
        }


        $bearer_token = bcrypt($login.$userDB->email.$this->bearer_token_secret);
        $refresh_token = bcrypt($login.$userDB->email.$this->refresh_token_secret);
        $time_token = time() + $this->time_token_set;

        $idRight = LinkRight::where('id_user', $userDB->id )->first();
        $right = Right::where('id', $idRight->id_rights )->first();


        User::where('id', $userDB->id)->update([
            'bearer_token' => $bearer_token,
            'refresh_token' => $refresh_token,
            'time_token' => $time_token
        ]);

        $response['data'] = ['bearer_token' => $bearer_token, 'refresh_token' => $refresh_token, 'time_token' => $time_token, 'right' => $right->name, 'name' => $right->ru_name];


        return response()->json($response);
    }



    public function token(){

        $response = ['data' => ['operation' => false], 'messages' => []];


        $token = $this->request->input('token');

        $userDB = User::where('refresh_token', $token)->first();
        if(!isset($userDB)){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Пользователь не найден', 'code' => 410];
            return response()->json($response);
        }

        $bearer_token = bcrypt($userDB->login.$userDB->email.$this->bearer_token_secret);
        $time_token = time() + $this->time_token_set;

        $idRight = LinkRight::where('id_user', $userDB->id )->first();
        $right = Right::where('id', $idRight->id_rights )->first();

        User::where('id', $userDB->id)->update([
            'bearer_token' => $bearer_token,
            'time_token' => $time_token
        ]);
        
        $response['data'] = ['bearer_token' => $bearer_token, 'time_token' => $time_token, 'right' => $right->name, 'name' => $right->ru_name];


        return response()->json($response);
    }



    public function exits(){

        $response = ['data' => ['operation' => true], 'messages' => []];

        User::where('id', $this->request->userId)->update([
            'bearer_token' => '',
            'refresh_token' => '',
            'time_token' => 0
        ]);

        return response()->json($response);
    }
}
