<?php

namespace App\Http\Middleware;
use Closure;

use App\LinkRight;
use App\Right;
use Auth;



class CenterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){

        // $segment = Request::segment(1);
        // if (Request::secure()) {
        // $value = Request::server('PATH_INFO');
        // $value = Request::header('Content-Type');



        // Получаем id политики для пользователя
        $link = LinkRight::where('id_user', Auth::id())->first();


        // Получаем политику
        /* $variable = Right::where('id', $link->id_rights )->first();
        $politic = json_decode($variable->tupe, true); */

        $tupeUser = Right::where('id', $link->id_rights )->first()->name;
        
        $politics = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/politics.json'), true);
        $politic = $politics[$tupeUser]['tupe'];


        // Проверяем данные по текущему url
        if(  array_key_exists($request->segment(1), $politic)  ){
            if(  array_key_exists('access', $politic[$request->segment(1)])  ){
                if( null === $request->segment(2) && $politic[$request->segment(1)]['access'] == 'params' ){
                    abort(404);
                }
            }
        }else{
            abort(404);
        }

        return $next($request);
        //return redirect()->route('in');
    }
}
