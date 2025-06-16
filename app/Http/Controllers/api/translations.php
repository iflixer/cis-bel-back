<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Seting;

use App\Translation;

class translations extends Controller
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

	public function search()
	{
		$translations = Translation::select();

		// search

		$search = $this->request->input('search');

		if ($search != '') {
			$translations
				->where('id', '=', $search)
				->orWhere('id_VDB', '=', $search)
				->orWhere('title', 'like', "%{$search}%")
				->orWhere('tag', 'like', "%{$search}%");
		}

		// offset

		$offset = 0;

		if ($this->request->input('offset') !== null && $this->request->input('offset') >= $offset)
			$offset = $this->request->input('offset');

		// limit

		$limit = 200;

		if ($this->request->input('limit') !== null && $this->request->input('limit') < $limit)
			$limit = $this->request->input('limit');

		// count

		$count = $translations->count();

		// get

		$translations = $translations
			->orderBy('id', 'ASC')
			->offset($offset)
			->limit($limit)
			->get()
			->toArray();

		return [
			'data' => [
				'method' => 'translations.search',
				'count' => $count,
				'items' => $translations
			],
      'messages' => []
		];
	}

	public function update()
	{
		$response = [];

		$messages = [
			[
				'tupe' => 'success',
				'message' => 'Обновлено'
			]
		];

		$data = $this->request->input('ad');

		Translation::where('id', $data['id'])
			->update([
				'title' => $data['title'],
				'tag' => $data['tag'],
				'priority' => $data['priority']
			]);

		return [
			'data' => $response,
			'messages' => $messages
		];
	}

}