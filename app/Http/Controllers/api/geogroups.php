<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\GeoGroup;
use App\IsoCountry;
use App\User;
use App\Right;
use App\LinkRight;
use DB;

class geogroups extends Controller{

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
        
        $geoGroups = GeoGroup::with(['countries' => function($query) {
            $query->select('id', 'name', 'iso_code', 'geo_group_id');
        }])->get();

        foreach ($geoGroups as $group) {
            $response[] = [
                'id' => $group->id,
                'name' => $group->name,
                'countries_count' => $group->countries->count(),
                'countries' => $group->countries->toArray(),
                'created_at' => $group->created_at ? $group->created_at->toDateTimeString() : null,
                'updated_at' => $group->updated_at ? $group->updated_at->toDateTimeString() : null
            ];
        }

        return ['data' => $response, 'messages' => $messages];
    }

    public function create(){
        $messages = [];
        
        $name = $this->request->input('name');
        $country_ids = $this->request->input('country_ids', []);

        if (empty($name)) {
            $response = ['status' => 'error', 'message' => 'Название группы не может быть пустым'];
            return ['data' => $response, 'messages' => $messages];
        }

        try {
            DB::beginTransaction();

            $geoGroup = GeoGroup::create([
                'name' => $name
            ]);

            if (!empty($country_ids)) {
                IsoCountry::whereIn('id', $country_ids)->update(['geo_group_id' => $geoGroup->id]);
            }

            DB::commit();

            $response = ['status' => 'success', 'message' => 'Гео-группа успешно создана', 'id' => $geoGroup->id];

            return ['data' => $response, 'messages' => $messages];
        } catch (\Exception $e) {
            DB::rollback();
            $response = ['status' => 'error', 'message' => 'Ошибка при создании гео-группы: ' . $e->getMessage()];
            return ['data' => $response, 'messages' => $messages];
        }
    }

    public function update(){
        $messages = [];
        
        $id = $this->request->input('id');
        $name = $this->request->input('name');
        $country_ids = $this->request->input('country_ids', []);

        if (empty($name)) {
            $response = ['status' => 'error', 'message' => 'Название группы не может быть пустым'];
            return ['data' => $response, 'messages' => $messages];
        }

        $geoGroup = GeoGroup::find($id);
        if (!$geoGroup) {
            $response = ['status' => 'error', 'message' => 'Гео-группа не найдена'];

            return ['data' => $response, 'messages' => $messages];
        }

        try {
            DB::beginTransaction();

            $geoGroup->update(['name' => $name]);
            IsoCountry::where('geo_group_id', $id)->update(['geo_group_id' => null]);
            if (!empty($country_ids)) {
                IsoCountry::whereIn('id', $country_ids)->update(['geo_group_id' => $id]);
            }

            DB::commit();

            $response = ['status' => 'success', 'message' => 'Гео-группа успешно обновлена'];

            return ['data' => $response, 'messages' => $messages];
        } catch (\Exception $e) {
            DB::rollback();
            $response = ['status' => 'error', 'message' => 'Ошибка при обновлении гео-группы: ' . $e->getMessage()];
            return ['data' => $response, 'messages' => $messages];
        }
    }

    public function delete(){
        $messages = [];
        
        $id = $this->request->input('id');

        $geoGroup = GeoGroup::find($id);
        if (!$geoGroup) {
            $response = ['status' => 'error', 'message' => 'Гео-группа не найдена'];

            return ['data' => $response, 'messages' => $messages];
        }

        try {
            DB::beginTransaction();
            IsoCountry::where('geo_group_id', $id)->update(['geo_group_id' => null]);
            $geoGroup->delete();
            DB::commit();

            $response = ['status' => 'success', 'message' => 'Гео-группа успешно удалена'];

            return ['data' => $response, 'messages' => $messages];
        } catch (\Exception $e) {
            DB::rollback();
            $response = ['status' => 'error', 'message' => 'Ошибка при удалении гео-группы: ' . $e->getMessage()];

            return ['data' => $response, 'messages' => $messages];
        }
    }

    public function countries(){
        $messages = [];
        $countries = IsoCountry::select('id', 'name', 'iso_code', 'geo_group_id')
                               ->orderBy('name')
                               ->get();
        $response = $countries->toArray();

        return ['data' => $response, 'messages' => $messages];
    }
}