<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;
use App\Video;

class blacklist extends Controller
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
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $limit = $this->request->input('limit') ?? 20;
        $offset = $this->request->input('offset') ?? 0;

        $query = Video::where('blacklisted', true);

        $count = $query->count();

        $videos = $query->select('id', 'id_VDB', 'ru_name', 'name', 'kinopoisk', 'year', 'tupe')
            ->orderBy('id', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        $response = [
            'count' => $count,
            'items' => $videos
        ];

        return ['data' => $response, 'messages' => $messages];
    }

    public function search()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $search = $this->request->input('search');

        if (empty($search)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан поисковый запрос']]];
        }

        $query = Video::select('id', 'id_VDB', 'ru_name', 'name', 'kinopoisk', 'year', 'tupe', 'blacklisted');

        if (is_numeric($search)) {
            $query = $query->where(function($q) use ($search) {
                $q->where('kinopoisk', $search)
                  ->orWhere('id_VDB', $search)
                  ->orWhere('id', $search);
            });
        } else {
            $query = $query->where('ru_name', 'like', '%' . $search . '%');
        }

        $videos = $query->limit(50)->get()->toArray();

        $response = $videos;

        return ['data' => $response, 'messages' => $messages];
    }

    public function add()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $id = $this->request->input('id');

        if (empty($id)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан ID видео']]];
        }

        $video = Video::where('id', $id)->first();

        if (!$video) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Видео не найдено']]];
        }

        if ($video->blacklisted) {
            return ['data' => $response, 'messages' => [['tupe' => 'warning', 'message' => 'Видео уже в черном списке']]];
        }

        Video::where('id', $id)->update(['blacklisted' => true]);

        $response = ['status' => true];
        $messages[] = ['tupe' => 'success', 'message' => 'Видео добавлено в черный список'];

        return ['data' => $response, 'messages' => $messages];
    }

    public function remove()
    {
        $response = [];
        $messages = [];

        if ($this->user['name'] != 'administrator') {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Доступ запрещен']]];
        }

        $id = $this->request->input('id');

        if (empty($id)) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Не указан ID видео']]];
        }

        $video = Video::where('id', $id)->first();

        if (!$video) {
            return ['data' => $response, 'messages' => [['tupe' => 'error', 'message' => 'Видео не найдено']]];
        }

        if (!$video->blacklisted) {
            return ['data' => $response, 'messages' => [['tupe' => 'warning', 'message' => 'Видео не находится в черном списке']]];
        }

        Video::where('id', $id)->update(['blacklisted' => null]);

        $response = ['status' => true];
        $messages[] = ['tupe' => 'success', 'message' => 'Видео удалено из черного списка'];

        return ['data' => $response, 'messages' => $messages];
    }
}
