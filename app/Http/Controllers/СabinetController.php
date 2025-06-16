<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;


use App\LinkRight;
use App\Right;
use App\User;
use App\Domain;
use Auth;


use App\Http\Controllers\SystemController as System;



class СabinetController extends Controller{

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

        $listDomains = [];

        $domains = Domain::where('id_parent', Auth::user()->id)->get();
        foreach ($domains as $value) {
            $listDomains[] = ['id' => $value->id, 'name' => $value->name];
        }

        // Данные компонента
        $component = $this->system->getPolitic( $this->request->segment(1) );
        $component['login'] = Auth::user()->login;
        $component['tupe'] = $this->system->name;

        $component['listDomains'] = $listDomains;
        


        // Данные для запуска vue
        $this->dataUser['title'] = 'cabinet page';
        $this->dataUser['nameComponent'] = 'component-cabinet';
        $this->dataUser['component'] = json_encode($component);

        $this->dataUser['messages'] = json_encode($this->messages);
        
        return view('video', $this->dataUser);

    }





    public function passUpdate(){
        $hashedPassword = User::where('id', Auth::id())->first()->password;
        if( Hash::check($this->request->oldPassword, $hashedPassword)  ){
            User::where('id',Auth::id())->update(['password' => Hash::make($this->request->newPassword)]);
            $this->messages[] = ['tupe'=>'info', 'message'=>'Пароль изменен'];
        }else{
            $this->messages[] = ['tupe'=>'error', 'message'=>'Пароль неверен'];
        }
        return $this->show();
    }



}
