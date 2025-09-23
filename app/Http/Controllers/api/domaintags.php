<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\DomainTag;
use App\Domain;
use App\LinkDomainTag;

class domaintags extends Controller
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

    public function get(){
        $response = [];
        $messages = [];

         if($this->user['name'] != 'administrator'){
             return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен. Ваша роль: ' . ($this->user['name'] ?? 'unknown')]] ];
         }
        
        $response = DomainTag::all()->toArray();
        return ['data' => $response,'messages' => $messages];
    }

    public function add(){
        $messages = [];
        $response = [];

         if($this->user['name'] != 'administrator'){
             return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен']] ];
         }

        $name = $this->request->input('name');
        $value = $this->request->input('value');
        $type = $this->request->input('type', 'domain_type');

        if($name == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указано название тега']] ];
        }

        if($value == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указано значение тега']] ];
        }

        $existingTag = DomainTag::where('name', $name)->first();
        if($existingTag){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Тег с таким названием уже существует']] ];
        }

        $tag = DomainTag::create([
            'name' => $name,
            'value' => $value,
            'type' => $type
        ]);

        $response = [ 'status' => true, 'data' => $tag->toArray() ];
        $messages[] = ['tupe'=>'success', 'message'=>'Тег успешно создан'];

        return ['data' => $response,'messages' => $messages];
    }

    public function update(){
        $messages = [];
        $response = [];

         if($this->user['name'] != 'administrator'){
             return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен']] ];
         }

        $id = $this->request->input('id');
        $name = $this->request->input('name');
        $value = $this->request->input('value');
        $type = $this->request->input('type', 'domain_type');

        if($id == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указан ID тега']] ];
        }

        if($name == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указано название тега']] ];
        }

        if($value == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указано значение тега']] ];
        }

        $tag = DomainTag::where('id', $id)->first();
        if(!$tag){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Тег не найден']] ];
        }

        $existingTag = DomainTag::where('name', $name)->where('id', '!=', $id)->first();
        if($existingTag){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Тег с таким названием уже существует']] ];
        }

        DomainTag::where('id', $id)->update([
            'name' => $name,
            'value' => $value,
            'type' => $type
        ]);

        $response = [ 'status' => true ];
        $messages[] = ['tupe'=>'success', 'message'=>'Тег успешно обновлен'];

        return ['data' => $response,'messages' => $messages];
    }

    public function delete(){
        $response = [];
        $messages = [];

         if($this->user['name'] != 'administrator'){
             return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен']] ];
         }

        $id = $this->request->input('id');
        if($id == ''){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указан ID тега']] ];
        }

        $tag = DomainTag::where('id', $id)->first();
        if(!$tag){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Тег не найден']] ];
        }

        DomainTag::where('id', $id)->delete();

        $response = [ 'status' => true ];
        $messages[] = ['tupe'=>'success', 'message'=>'Тег успешно удален'];

        return ['data' => $response,'messages' => $messages];
    }

    public function domainsList(){
        $response = [];
        $messages = [];

        if($this->user['name'] != 'administrator'){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен']] ];
        }

        $domains = Domain::all(['id','name','id_parent'])->toArray();
        $userIds = array_values(array_unique(array_map(function($d){ return $d['id_parent']; }, $domains)));
        $users = User::whereIn('id', $userIds)->get(['id','email'])->keyBy('id');

        $domainIds = array_map(function($d){ return $d['id']; }, $domains);
        $links = LinkDomainTag::whereIn('id_domain', $domainIds)->get(['id_domain','id_tag']);
        $tagIds = array_values(array_unique($links->pluck('id_tag')->toArray()));
        $tags = DomainTag::whereIn('id', $tagIds)->get(['id','name','value'])->keyBy('id');

        $tagsByDomain = [];
        foreach ($links as $l) {
            $tagsByDomain[$l->id_domain] = $tagsByDomain[$l->id_domain] ?? [];
            if (isset($tags[$l->id_tag]))
                $tagsByDomain[$l->id_domain][] = $tags[$l->id_tag];
        }

        foreach ($domains as $d) {
            $response[] = [
                'id' => $d['id'],
                'name' => $d['name'],
                'user_email' => isset($users[$d['id_parent']]) ? $users[$d['id_parent']]->email : null,
                'tags' => isset($tagsByDomain[$d['id']]) ? array_values(array_map(function($t){ return $t->toArray(); }, $tagsByDomain[$d['id']])) : []
            ];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function getAll(){
        $response = DomainTag::all(['id','name','value','type'])->toArray();
        return ['data' => $response, 'messages' => []];
    }

    public function setForDomain(){
        $messages = [];
        $response = [];

        if($this->user['name'] != 'administrator'){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Доступ запрещен']] ];
        }

        $domainId = intval($this->request->input('domain_id'));
        $tagIds = $this->request->input('tag_ids');

        if(!$domainId){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Не указан ID домена']] ];
        }

        if(!is_array($tagIds)){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Неверный формат списка тегов']] ];
        }

        $domain = Domain::where('id', $domainId)->first();
        if(!$domain){
            return ['data' => $response,'messages' => [['tupe'=>'error', 'message'=>'Домен не найден']] ];
        }

        $validTags = DomainTag::whereIn('id', $tagIds)->pluck('id')->toArray();

        LinkDomainTag::where('id_domain', $domainId)->delete();
        foreach ($validTags as $tid) {
            LinkDomainTag::create([
                'id_domain' => $domainId,
                'id_tag' => $tid
            ]);
        }

        $messages[] = ['tupe'=>'success', 'message'=>'Теги обновлены'];
        $response = ['status' => true];
        return ['data' => $response,'messages' => $messages];
    }
}
