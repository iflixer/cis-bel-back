<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

use App\User;
use App\LinkRight;
use App\Right;

use App\Apilog;

use DB;
use DatePeriod;
use DateTime;
use DateInterval;

class CustomThrottleMiddleware extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */



    public function handle($request, Closure $next, $maxAttempts = 30, $decayMinutes = 1){

        $messages = [];
        // Выбираем ключ

        $key = $request->input('account_key');

        if (!$key)
            $key = $request->input('token');

        // remember_token

        


        // Проверка ключа на существование и заполненость
        if(isset($key) && $key != ""){
            // dump($key);

            // Делаем выборку из базы
            $user = User::where('api_key', $key)->get();
            
            // Проверяем результат выборки на наличие данных
            if( !$user->isEmpty() ){

                // Выбираем id политики юзера и проверяем на клиента
                $idLink = LinkRight::where('id_user', $user[0]->id)->first();

                // Получаем список доступных методов
                $tupeUser = Right::where('id', $idLink->id_rights )->first()->name;
                $politics = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/politics.json'), true);
                $methods = $politics[$tupeUser]['methods'];

                $request->userId = $user[0]->id;
                $request->userRight = $tupeUser;

                // Определяем доступность метода
                if( array_key_exists($request->route('method'), $methods) ){

                    // $load = round($this->getServerCPULoad(), 0);
                    $load = 0;

                    $apilogs = Apilog::where('date', date('Y-m-d'))->first();
                    if(!isset($apilogs)){
                        Apilog::create([
                            'date' => date('Y-m-d'),
                            'count' => 1,
                            'loading' => $load
                        ]);
                    }else{
                        $cpu = $apilogs->loading;
                        if($cpu < $load){
                            $cpu = $load;
                        }
                        Apilog::where('id', $apilogs->id)->update([
                            'count' => $apilogs->count + 1,
                            'loading' => $cpu
                        ]);
                    }
                    
                    if($idLink->id_rights != 1){
                        // Админу картбланш
                        return $next($request);
                    }else{

                        $maxAttempts = 300;

                        // Клиенту тротлинг
                        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
                            return $this->buildResponse($key, $maxAttempts);
                        }

                        // Если ограничений нет то идем дальше
                        $this->limiter->hit($key, $decayMinutes);
                        $response = $next($request);
                        return $this->addHeaders( $response, $maxAttempts, $this->calculateRemainingAttempts($key, $maxAttempts) );
                    } 

                }else{
                    // echo "error not method";
                    $messages[] = ['tupe'=>'error', 'message'=>'Метод отсутствует'];
                    return response()->json( ['messages' => $messages] );
                }

            }else{
                // echo "error not user";
                $messages[] = ['tupe'=>'error', 'message'=>'Юзер отсутствует'];
                return response()->json( ['messages' => $messages] );
            }

        }else{
            // echo "error not key";
            $messages[] = ['tupe'=>'error', 'message'=>'Не указанн токен доступа'];
            return response()->json( ['messages' => $messages] );
        }



    }



    protected function getServerCPULoad(){
        return null; // fix delay

        //проверяем возможность чтения виртуальной директории
        if (@is_readable('/proc/stat')){
         
            //делаем первый замер
            $file_first = file("/proc/stat");
            
            //определяем значения состояний (описаны выше)
            $tmp_first = explode(" ",$file_first[0]);
            
            $cpu_user_first = $tmp_first[2];
            $cpu_nice_first = $tmp_first[3];
            $cpu_sys_first = $tmp_first[4];
            $cpu_idle_first = $tmp_first[5];
            $cpu_io_first = $tmp_first[6];
            
            sleep(2);//промежуток до второго замера
            
            //делаем второй замер
            $file_second = file("/proc/stat");
            $tmp_second = explode(" ",$file_second[0]);
            
            $cpu_user_second= $tmp_second[2];
            $cpu_nice_second= $tmp_second[3];
            $cpu_sys_second = $tmp_second[4];
            $cpu_idle_second= $tmp_second[5];
            $cpu_io_second = $tmp_second[6];
            
            //определяем разницу использованного процессорного времени
            $diff_used = ($cpu_user_second-$cpu_user_first)+($cpu_nice_second-$cpu_nice_first)+($cpu_sys_second-$cpu_sys_first)+($cpu_io_second-$cpu_io_first);
            
            //определяем разницу общего процессорного времени
            $diff_total = ($cpu_user_second-$cpu_user_first)+(
            
            $cpu_nice_second-$cpu_nice_first)+($cpu_sys_second-$cpu_sys_first)+($cpu_io_second-$cpu_io_first)+($cpu_idle_second-$cpu_idle_first);
            
            // определение загрузки cpu
            $cpu = round($diff_used/$diff_total, 2);
            
            return round($cpu * 100); //(от 0 до 1, если нужно в % - x100)

        }
        return null;
    }






}
