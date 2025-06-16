<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\User;
use Auth;

use App\Http\Controllers\SystemController as System;



class UsersController extends Controller{

    protected $request;
    protected $system;
    protected $dataUser;
    protected $messages;

    public function __construct(Request $request){
        $this->request = $request;
        $this->system = new System();
        $this->messages = $this->system->messages;

        $this->dataUser = [
            'tupe' => $this->system->name,
            'user' => Auth::user()->login,

            'key' => Auth::user()->api_key,
            'components' => $this->system->components(),
            'menu' => $this->system->getMenu(),
            'header' => $this->system->getHeader($this->request->segment(1))
        ];
    }


    public function show(){

        $host = $this->system->getPolitic( $this->request->segment(1) );

        $this->dataUser['messages'] = json_encode($this->messages);
        $this->dataUser['title'] = 'users page';
        $this->dataUser['nameComponent'] = 'component-users';
        $this->dataUser['component'] = json_encode($host);
        
        return view('video', $this->dataUser);
    }
}
