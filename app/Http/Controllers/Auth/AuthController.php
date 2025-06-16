<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

use Session;


use App\LinkRight;


class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;






    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    protected $username = 'login';
    protected $redirectAfterLogout = '/';


    


    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware($this->guestMiddleware(), ['except' => 'logout']);

        /*
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        
        if(attempt()) {
            $this->clearLoginAttempts($request);
        }else {
            $this->incrementLoginAttempts($request);
        }*/

        //print_r( Session::all() );
    }






    public function maxAttempts(){
        //Lock on 4th Failed Login Attempt
        return 3;
    }
    public function decayMinutes(){
        //Lock for 2 minutes
        return 2;
    }



    


    public function loginUsername(){
        return property_exists($this, 'username') ? $this->username : 'login';
    }

    protected function redirectTo(){
        return url('/home');
    }




    protected function getCredentials(Request $request){

        $credentials = $request->only($this->loginUsername(), 'password');

        $user = User::where('login', $request->input('login'))->first()->toArray();
        if( $user['status'] === '0' ){
           $credentials['status'] = 42; 
        }

        return $credentials;
    }






    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $messej = [];

        return Validator::make($data, [
            'login' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|confirmed'
        ], $messej);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'api_key' => md5(time().$data['login']),
            'login' => $data['login'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'status' => 1
        ]);

        LinkRight::create(['id_user' => $user->id, 'id_rights' => 1]);

        return $user;
    }


}
