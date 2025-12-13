<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Domain;
use App\DomainTag;
use App\LinkDomainTag;
use App\DomainType;
use DB;

class domains extends Controller
{

    public $request;
    protected $user;

    public function __construct(Request $request){
        $this->request = $request;
        if( $request->input('account_key') != ''){
            $this->user = User::where('api_key', $request->input('account_key'))->first()->toArray();
        }else{
            $this->user = User::where('id', $request->userId)->first()->toArray();
        }

        $idRight = LinkRight::where('id_user', $this->user['id'] )->first();
        $right = Right::where('id', $idRight->id_rights )->first()->toArray();

        foreach ($right as $key => $value) {
            if($key != 'id'){
                $this->user[$key] = $value;
            }
        }
    }




    // Добавление сайта в систему
    public function add(){

        $messages = [];
        $response = [];

        $domain = $this->request->input('domain');
        if($domain == ''){
            return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указано название Домена или Телеграм канала']] ];
        }

        $tg = false;

        if (!preg_match('#^@[a-z0-9_]+$#i', $domain)) {

            if(strpos($domain, '.') === false){
                return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Некорректный формат Домена или Телеграм канала']] ];
            }

            $domain = preg_replace("#(http(s)?:)?//#i", '', $domain);

            if($domain == ''){
                return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Некорректный формат Домена или Телеграм канала']] ];
            }

            $path = explode('/', $domain);
            $domain = isset($path[0]) ? $path[0] : '';

            if($domain == ''){
                return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Некорректный формат Домена или Телеграм канала']] ];
            }

        } else {
            $tg = true;
        }

        $domainDB = Domain::where('name', $domain)->first();

        // Проверка наличия домена в базе
        if(!isset($domainDB)){

            // Проверяем фаил на домене
            // $url = 'http://'.$domain.'/'.$this->user['api_key'].'.txt';
            // $headers = @get_headers($url);
            if(true){ // preg_match("|200|", $headers[0])

                $status = 0;
                if ($tg)
                    $complitMessage = ['tupe'=>'info', 'message'=>'Телеграм канал добавлен, дождитесь подтверждения администратора'];
                else
                    $complitMessage = ['tupe'=>'info', 'message'=>'Домен добавлен, дождитесь подтверждения администратора'];
                // Если запрос от администратора
                if($this->user['name'] != 'client'){
                    $status = 1;
                    if ($tg)
                        $complitMessage = ['tupe'=>'info', 'message'=>'Телеграм канал добавлен'];
                    else
                        $complitMessage = ['tupe'=>'info', 'message'=>'Домен добавлен'];
                }

                // Создаем запись
                $lastId = Domain::create([
                    'id_parent' => $this->user['id'],
                    'name' => $domain,
                    'status' => $status,
                    'player' => file_get_contents($_SERVER['DOCUMENT_ROOT'].'/player.json'),
                    'new_player' => file_get_contents($_SERVER['DOCUMENT_ROOT'].'/player.json')
                ])->id;
                $response = [ 'status' => true, 'data' => ['id' => $lastId, 'name' => $domain, 'status' => $status] ];
                $messages[] = $complitMessage;

            }else{
                $messages[] = ['tupe'=>'error', 'message'=>'Фаил не найден, проверьте доступность.'];
                $response = [ 'status' => false ];
            }

        }else{
            $messages[] = ['tupe'=>'error', 'message'=>'Домен или Телеграм канал уже зарегистрирован'];
            $response = [ 'status' => false ];
        }

        return ['data' => $response,'messages' => $messages];
    }


    
    public function delete(){
        $response = [];
        $messages = [];

        $idDomain = $this->request->input('id');
        if($idDomain == ''){
            return ['response' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указанн idDomain']] ];
        }

        $domain = Domain::where('id',$idDomain)->first();

        if (strpos($domain->name, '@') !== false)
            $message = 'Телеграм канал удален';
        else
            $message = 'Домен удален';

        Domain::where('id',$idDomain)->delete();

        return ['data' => $response,'messages' => [['tupe'=>'info', 'message'=>$message]]];
    }





    public function get(){
        $response = [];
        $messages = [];
        $response = Domain::where('id_parent', $this->user['id'])->get()->toArray();
        return ['data' => $response,'messages' => $messages];
    }


    public function setBlack(){
        $response = [];
        $messages = [];
        Domain::where('id', $this->request->input('id'))->update(['black_ad_on' => $this->request->input('on')]);
        return ['data' => $response,'messages' => $messages];
    }


    public function updatePlayer(){
        $response = [];
        $messages = [];
        Domain::where('id', $this->request->input('id'))->update([ 'new_player' => $this->request->input('data') ]);
        return ['data' => $response,'messages' => $messages];
    }


    public function notComplit(){
        $response = [];
        $messages = [];
        $idDomain = $this->request->input('id');
        if($idDomain == ''){
            return ['response' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн idDomain']];
        }
        Domain::where('id', $idDomain)->update([ 'status' => '2' ]);
        
        $updatedDomain = Domain::where('id', $idDomain)->first();
        if ($updatedDomain) {
            $response = [
                'id' => $updatedDomain->id,
                'name' => $updatedDomain->name,
                'status' => $updatedDomain->status,
                'id_parent' => $updatedDomain->id_parent
            ];
        }
        
        return ['data' => $response,'messages' => [['tupe'=>'info', 'message'=>'Домен отклонен']]];
    }

    public function complit(){
        $response = [];
        $messages = [];
        $idDomain = $this->request->input('id');
        if($idDomain == ''){
            return ['response' => $response,'messages' => ['tupe'=>'error', 'message'=>'Не указанн idDomain']];
        }
        Domain::where('id', $idDomain)->update([ 'status' => '1' ]);
        
        $updatedDomain = Domain::where('id', $idDomain)->first();
        if ($updatedDomain) {
            $response = [
                'id' => $updatedDomain->id,
                'name' => $updatedDomain->name,
                'status' => $updatedDomain->status,
                'id_parent' => $updatedDomain->id_parent
            ];
        }
        
        return ['data' => $response,'messages' => [['tupe'=>'success', 'message'=>'Домен одобрен']]];
    }

    public function importFromCsv(){
        $response = [];
        $messages = [];
        
        $domains = $this->request->input('domains');
        $domainTypeId = $this->request->input('domain_type_id');
        
        if (!is_array($domains) || empty($domains)) {
            return ['data' => ['success_count' => 0, 'errors' => ['Не предоставлен список доменов']], 'messages' => [['tupe'=>'error', 'message'=>'Не предоставлен список доменов']]];
        }
        
        if (!$domainTypeId) {
            return ['data' => ['success_count' => 0, 'errors' => ['Не выбран тип домена']], 'messages' => [['tupe'=>'error', 'message'=>'Не выбран тип домена']]];
        }
        
        $domainType = DomainType::where('id', $domainTypeId)->first();
        if (!$domainType) {
            return ['data' => ['success_count' => 0, 'errors' => ['Некорректный тип домена']], 'messages' => [['tupe'=>'error', 'message'=>'Некорректный тип домена']]];
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($domains as $domain) {
            $domain = trim($domain);
            if (empty($domain)) {
                continue;
            }
            
            try {
                $tg = false;
                if (!preg_match('#^@[a-z0-9_]+$#i', $domain)) {
                    if(strpos($domain, '.') === false){
                        $errors[] = "Некорректный формат домена: {$domain}";
                        continue;
                    }
                    
                    $domain = preg_replace("#(http(s)?:)?//#i", '', $domain);
                    
                    if($domain == ''){
                        $errors[] = "Некорректный формат домена: {$domain}";
                        continue;
                    }
                    
                    $path = explode('/', $domain);
                    $domain = isset($path[0]) ? $path[0] : '';
                    
                    if($domain == ''){
                        $errors[] = "Некорректный формат домена: {$domain}";
                        continue;
                    }
                } else {
                    $tg = true;
                }
                
                $existingDomain = Domain::where('name', $domain)->first();
                if ($existingDomain) {
                    $errors[] = "Домен уже существует: {$domain}";
                    continue;
                }
                
                $status = 0;
                if($this->user['name'] != 'client'){
                    $status = 1;
                }
                
                $newDomain = Domain::create([
                    'id_parent' => $this->user['id'],
                    'domain_type_id' => $domainTypeId,
                    'name' => $domain,
                    'status' => $status,
                    'player' => file_get_contents($_SERVER['DOCUMENT_ROOT'].'/player.json'),
                    'new_player' => file_get_contents($_SERVER['DOCUMENT_ROOT'].'/player.json')
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Ошибка при импорте домена {$domain}: " . $e->getMessage();
            }
        }
        
        $response = [
            'success_count' => $successCount,
            'total_count' => count($domains),
            'errors' => $errors
        ];
        
        $message = "Импортировано доменов: {$successCount}/" . count($domains);
        if (count($errors) > 0) {
            $message .= ". Ошибок: " . count($errors);
        }
        
        $messageType = count($errors) > 0 ? 'warning' : 'success';
        $messages[] = ['tupe' => $messageType, 'message' => $message];
        
        return ['data' => $response, 'messages' => $messages];
    }

    public function getAll(){
        $response = [];
        $messages = [];

        $query = Domain::select(
            'domains.*',
            'users.login as user_login',
            'domain_types.name as domain_type_name',
            DB::raw('COALESCE(loads_stats.cnt, 0) as loads_24h')
        )
        ->leftJoin('users', 'domains.id_parent', '=', 'users.id')
        ->leftJoin('domain_types', 'domains.domain_type_id', '=', 'domain_types.id')
        ->leftJoin(DB::raw('(SELECT domain_id, COUNT(*) as cnt FROM player_pay_log WHERE event = "load" AND created_at >= NOW() - INTERVAL 24 HOUR GROUP BY domain_id) as loads_stats'), 'domains.id', '=', 'loads_stats.domain_id');

        $searchUser = $this->request->input('search_user');
        if ($searchUser) {
            $query->where('users.login', 'like', "%{$searchUser}%");
        }

        $searchDomain = $this->request->input('search_domain');
        if ($searchDomain) {
            $query->where('domains.name', 'like', "%{$searchDomain}%");
        }

        $count = $query->count();
        $orderBy = $this->request->input('order_by', 'id');
        $orderDirection = $this->request->input('order_direction', 'DESC');
        $columnMap = [
            'id' => 'domains.id',
            'name' => 'domains.name',
            'user_login' => 'users.login',
            'domain_type_name' => 'domain_types.name',
            'status' => 'domains.status',
            'loads_24h' => 'loads_24h'
        ];

        $sortColumn = isset($columnMap[$orderBy]) ? $columnMap[$orderBy] : 'domains.id';
        $query->orderBy($sortColumn, $orderDirection);

        $limit = $this->request->input('limit', 20);
        $offset = $this->request->input('offset', 0);

        if ($limit > 200) {
            $limit = 200;
        }

        $query->offset($offset)->limit($limit);
        $items = $query->get()->toArray();

        $response = [
            'count' => $count,
            'items' => $items
        ];

        return ['data' => $response, 'messages' => $messages];
    }

    public function updateDomainType(){
        $response = [];
        $messages = [];

        $domainId = $this->request->input('domain_id');
        $domainTypeId = $this->request->input('domain_type_id');

        if (!$domainId) {
            return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Не указан ID домена']]];
        }

        $domain = Domain::where('id', $domainId)->first();
        if (!$domain) {
            return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Домен не найден']]];
        }

        if ($domainTypeId !== null && $domainTypeId !== '') {
            $domainType = DomainType::where('id', $domainTypeId)->first();
            if (!$domainType) {
                return ['data' => $response, 'messages' => [['tupe'=>'error', 'message'=>'Тип домена не найден']]];
            }
            $domainTypeName = $domainType->name;
        } else {
            $domainTypeId = null;
            $domainTypeName = null;
        }

        Domain::where('id', $domainId)->update(['domain_type_id' => $domainTypeId]);

        $response = [
            'id' => $domain->id,
            'domain_type_id' => $domainTypeId,
            'domain_type_name' => $domainTypeName
        ];

        $messages[] = ['tupe'=>'success', 'message'=>'Тип домена успешно обновлен'];

        return ['data' => $response, 'messages' => $messages];
    }

}