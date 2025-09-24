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
use App\Helpers\Cloudflare;
use App\IsoCountry;
use App\PlayerPay;

use App\Http\Controllers\SystemController as System;





class PlayerController extends Controller
{
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
        $this->dataUser['title'] = 'player page';
        $this->dataUser['nameComponent'] = 'component-player';
        $this->dataUser['component'] = json_encode($host);

        return view('video', $this->dataUser);

    }
}
