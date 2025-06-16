<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;


use App\LinkRight;
use App\Right;
use App\Video;
use App\File;
use App\User;
use Auth;

use App\Http\Controllers\SystemController as System;



class VideoController extends Controller{

    
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
        if(isset($host['view'])){
            if($host['view'] == 'video'){
                return $this->showVideo();
            }
        } 

        $this->dataUser['messages'] = json_encode($this->messages);
        $this->dataUser['title'] = 'home page';
        $this->dataUser['nameComponent'] = 'component-home';
        $this->dataUser['component'] = json_encode($host);
        
        return view('video', $this->dataUser);

    }




    public function showVideo(){

        $host = $this->system->getPolitic( $this->request->segment(1) );
        $host['videodb'] = [
            'count_vdb' => File::where('sids','VDB')->count()
        ];

        $this->dataUser['messages'] = json_encode($this->messages);
        $this->dataUser['title'] = 'video page';
        $this->dataUser['nameComponent'] = 'component-video';
        $this->dataUser['component'] = json_encode($host);

        return view('video', $this->dataUser);
    }









    /* Системный метод */
    public function activated($key){

        if(Auth::user()->api_key == $key){
            
            User::where('api_key',Auth::user()->api_key)->update(['status' => 2]);

            unset($this->messages['userActivatedAccount']);
            $this->messages['system'] = ['tupe'=>'info', 'message'=>'Ваш аккаунт подтвержден'];
        }
          
          
        return $this->show();
    }


    









    public function pages(){

        $this->dataUser['title'] = 'pages page';

        return view('video', $this->dataUser);
    }





    public function page($page){

        $this->dataUser['title'] = $page.' page';

        return view('video', $this->dataUser);
    }




}
