<?php

namespace App\Http\Middleware;

use App\User;
use App\Right;
use App\LinkRight;

use Closure;

class VideoTouchMiddleware
{
    public function handle($request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || substr($authHeader, 0, 7) !== 'Bearer ') {
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response, 401);
        }

        $token = substr($authHeader, 7);

        if (empty($token)) {
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response, 401);
        }

        $userDB = User::where('bearer_token', $token)->first();
        if(!isset($userDB)){
            $response['messages'][] = ['type'=>'error', 'message'=>'Неверный токен', 'code' => 412];
            return response()->json($response, 401);
        }

        if ($userDB->time_token < time()) {
            $response['messages'][] = ['type'=>'error', 'message'=>'Токен истек', 'code' => 412];
            return response()->json($response, 401);
        }

        $idRight = LinkRight::where('id_user', $userDB->id)->first();
        $right = Right::where('id', $idRight->id_rights)->first();

        if ($right->name !== 'administrator') {
            $response['messages'][] = ['type'=>'error', 'message'=>'Доступ разрешен только администраторам', 'code' => 403];
            return response()->json($response, 403);
        }

        $request->userId = $userDB->id;
        $request->userRight = $right->name;

        return $next($request);
    }
}
