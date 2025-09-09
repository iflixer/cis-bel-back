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
        $authHeader = $request->header('Authorization');

        if (!$authHeader || substr($authHeader, 0, 7) !== 'Bearer ') {
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response);
        }
        
        $token = substr($authHeader, 7);

        if (empty($token)) {
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response);
        }

        $userDB = User::where('bearer_token', $token)->first();
        if(!isset($userDB)){
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response);
        }

        if ($userDB->time_token < time()) {
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response);
        }

        $idRight = LinkRight::where('id_user', $userDB->id )->first();
        $right = Right::where('id', $idRight->id_rights )->first();


        $request->userId = $userDB->id;
        $request->userRight = $right->name;

        
        $politic = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/politics.json'), true)[$right->name]['methods'];


        // Определяем доступность метода
        if( !(array_key_exists($request->route('method'), $politic) || $request->segment(2) == "exits") ){

            $response['messages'][] = ['type'=>'error', 'message'=>'Операция запрещена', 'code' => 4];
            return response()->json($response);
        }
        

        return $next($request);
    }
}
