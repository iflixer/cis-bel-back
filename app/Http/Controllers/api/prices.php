<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Right;
use App\LinkRight;
use App\Services\PriceService;
use App\DomainType;

class prices extends Controller
{
    public $request;
    protected $user;
    protected $priceService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->priceService = new PriceService();
        
        if ($request->input('account_key') != '') {
            $userRecord = User::where('api_key', $request->input('account_key'))->first();
        } else {
            $userRecord = User::where('id', $request->userId)->first();
        }

        if (!$userRecord) {
            throw new \Exception('User not found');
        }

        $this->user = $userRecord->toArray();

        $idRight = LinkRight::where('id_user', $this->user['id'])->first();
        if (!$idRight) {
            throw new \Exception('User rights not found');
        }

        $right = Right::where('id', $idRight->id_rights)->first();
        if (!$right) {
            throw new \Exception('Rights not found');
        }

        $rightArray = $right->toArray();
        foreach ($rightArray as $key => $value) {
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
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен. Только администраторы могут управлять ценами.']]];
        }

        try {
            $response = $this->priceService->getPriceMatrix();
        } catch (\Exception $e) {
            $messages[] = ['tupe' => 'error', 'message' => 'Ошибка получения матрицы цен: ' . $e->getMessage()];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function update()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен. Только администраторы могут управлять ценами.']]];
        }

        $geoGroupId = $this->request->input('geo_group_id');
        $domainTypeName = $this->request->input('domain_type_name');
        $priceCents = $this->request->input('price_cents');

        if (empty($geoGroupId)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан ID географической группы']]];
        }

        if (empty($domainTypeName)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указано название типа домена']]];
        }

        if (!is_numeric($priceCents) || $priceCents < 0 || !is_int($priceCents + 0)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Цена должна быть положительным целым числом (USD центы)']]];
        }

        if ($priceCents > 1000000) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Цена не может превышать 1,000,000 центов ($10,000)']]];
        }

        try {
            $domainType = DomainType::where('name', $domainTypeName)->first();
            
            if (!$domainType) {
                return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Тип домена не найден']]];
            }

            $priceRecord = $this->priceService->setVideoPriceById($geoGroupId, $domainType->id, $priceCents);

            $response = [
                'status' => true,
                'price_record' => $priceRecord->toArray()
            ];
            $messages[] = ['tupe' => 'success', 'message' => 'Цена успешно обновлена'];
        } catch (\Exception $e) {
            $messages[] = ['tupe' => 'error', 'message' => 'Ошибка обновления цены: ' . $e->getMessage()];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function getMatrix()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен. Только администраторы могут управлять ценами.']]];
        }

        try {
            $matrix = $this->priceService->getPriceMatrix();
            $domainTypes = $this->priceService->getDomainTypes();
            $geoGroups = $this->priceService->getGeoGroups();
            $basePrice = $this->priceService->getBasePrice();

            $response = [
                'matrix' => $matrix,
                'domain_types' => $domainTypes,
                'geo_groups' => $geoGroups,
                'base_price' => $basePrice
            ];
        } catch (\Exception $e) {
            $messages[] = ['tupe' => 'error', 'message' => 'Ошибка получения данных матрицы: ' . $e->getMessage()];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function getDomainTypes()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        try {
            $response = $this->priceService->getDomainTypes();
        } catch (\Exception $e) {
            $messages[] = ['tupe' => 'error', 'message' => 'Ошибка получения типов доменов: ' . $e->getMessage()];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function getPrice()
    {
        $response = [];
        $messages = [];

        $geoGroupId = $this->request->input('geo_group_id');
        $domainType = $this->request->input('domain_type');

        if (empty($geoGroupId) || empty($domainType)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указаны обязательные параметры']]];
        }

        try {
            $priceCents = $this->priceService->getVideoPrice($geoGroupId, $domainType);
            $response = ['price_cents' => $priceCents];
        } catch (\Exception $e) {
            $messages[] = ['tupe' => 'error', 'message' => 'Ошибка получения цены: ' . $e->getMessage()];
        }

        return ['data' => $response, 'messages' => $messages];
    }
}