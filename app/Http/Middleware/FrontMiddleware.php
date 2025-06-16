<?php

namespace App\Http\Middleware;

use App\User;
use App\Right;
use App\LinkRight;

use Closure;

class FrontMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = substr( $request->header('Authorization'), 7);

        $userDB = User::where('bearer_token', $token)->first();
        if(!isset($userDB)){
            $response['messages'][] = ['tupe'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response);
        }

        $idRight = LinkRight::where('id_user', $userDB->id )->first();
        $right = Right::where('id', $idRight->id_rights )->first();


        $request->userId = $userDB->id;
        $request->userRight = $right->name;

        
        $politic = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/politics.json'), true)[$right->name]['methods'];


        // Определяем доступность метода
        if( !(array_key_exists($request->route('method'), $politic) || $request->segment(2) == "exits") ){

            $response['messages'][] = ['tupe'=>'error', 'message'=>'Операция запрещена', 'code' => 4];
            return response()->json($response);
        }
        

        return $next($request);
    }
}
