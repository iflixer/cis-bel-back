<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;
use App\DomainType;

class domaintypes extends Controller
{
    public $request;

    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        if ($request->input('account_key') != '') {
            $this->user = User::where('api_key', $request->input('account_key'))->first()->toArray();
        } else {
            $this->user = User::where('id', $request->userId)->first()->toArray();
        }

        $idRight = LinkRight::where('id_user', $this->user['id'])->first();
        $right = Right::where('id', $idRight->id_rights)->first()->toArray();

        foreach ($right as $key => $value) {
            if ($key != 'id') {
                $this->user[$key] = $value;
            }
        }
    }

    public function get()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен. Ваша роль: ' . ($this->user['name'] ?? 'unknown')]]];
        }

        $response = DomainType::all()->toArray();
        return ['data' => $response, 'messages' => $messages];
    }

    public function add()
    {
        $messages = [];
        $response = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $name = $this->request->input('name');
        $value = $this->request->input('value');

        if ($name == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указано название типа домена']]];
        }

        if ($value == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указано значение типа домена']]];
        }

        $existingType = DomainType::where('name', $name)->orWhere('value', $value)->first();
        if ($existingType) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Тип домена с таким названием или значением уже существует']]];
        }

        $domainType = DomainType::create([
            'name' => $name,
            'value' => $value
        ]);

        $response = ['status' => true, 'data' => $domainType->toArray()];
        $messages[] = ['tupe' => 'success', 'message' => 'Тип домена успешно создан'];

        return ['data' => $response, 'messages' => $messages];
    }

    public function update()
    {
        $messages = [];
        $response = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $id = $this->request->input('id');
        $name = $this->request->input('name');
        $value = $this->request->input('value');

        if ($id == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан ID типа домена']]];
        }

        if ($name == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указано название типа домена']]];
        }

        if ($value == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указано значение типа домена']]];
        }

        $domainType = DomainType::where('id', $id)->first();
        if (!$domainType) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Тип домена не найден']]];
        }

        $existingType = DomainType::where(function ($query) use ($name, $value) {
            $query->where('name', $name)->orWhere('value', $value);
        })->where('id', '!=', $id)->first();

        if ($existingType) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Тип домена с таким названием или значением уже существует']]];
        }

        DomainType::where('id', $id)->update([
            'name' => $name,
            'value' => $value
        ]);

        $response = ['status' => true];
        $messages[] = ['tupe' => 'success', 'message' => 'Тип домена успешно обновлен'];

        return ['data' => $response, 'messages' => $messages];
    }

    public function delete()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $id = $this->request->input('id');
        if ($id == '') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан ID типа домена']]];
        }

        $domainType = DomainType::where('id', $id)->first();
        if (!$domainType) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Тип домена не найден']]];
        }

        $domainsCount = $domainType->domains()->count();
        if ($domainsCount > 0) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Невозможно удалить тип домена. Используется в ' . $domainsCount . ' доменах']]];
        }

        DomainType::where('id', $id)->delete();

        $response = ['status' => true];
        $messages[] = ['tupe' => 'success', 'message' => 'Тип домена успешно удален'];

        return ['data' => $response, 'messages' => $messages];
    }

    public function getAll()
    {
        $response = DomainType::all(['id', 'name', 'value'])->toArray();
        return ['data' => $response, 'messages' => []];
    }
}