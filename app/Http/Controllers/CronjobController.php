<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\LinkRight;
use App\Right;
use App\User;
use App\Video;
use App\File;
use App\Domain;
use App\Seting;

use App\Translation;
use App\Videodb;

use App\Country;
use App\Genre;
use App\Link_country;
use App\Link_genre;

use Mail;
use DB;

// include $_SERVER['DOCUMENT_ROOT'].'/resources/simple_html_dom.php';

use JonnyW\PhantomJs\Client;

class CronjobController extends Controller
{

	public $request;
	protected $loginVDB; // = 'kolobock'
	protected $passVDB; // = '5HxL2P2Yw1yq'

	// protected $adress = 'https://api.kholobok.biz/show/';
	// protected $adress = 'https://bel-cdn.printhouse.casa/show/';
	protected $adress = 'https://bel-cdn.printhouse.casa/show/';

	protected $usesApi = "App\Http\Controllers\api\\";

	public function __construct(Request $request)
	{
		$this->request = $request;

		$this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
		$this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
	}

	public function videodb()
	{
		set_time_limit(0);

		//

		$videodb = Videodb::select('last_accepted_at')->where('method', 'sync')->first()->toArray();
		$last_created_at = strtotime($videodb['last_accepted_at']);

		//

		$limit = 100;
		$offset = 0;

		$stop_update = false;

		$medias = [];

		while (!$stop_update) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($curl, CURLOPT_USERPWD, $this->loginVDB.':'.$this->passVDB);
			curl_setopt($curl, CURLOPT_URL, "https://videodb.win/api/v1/medias?ordering=-created&limit={$limit}&offset={$offset}");
			$rezult = json_decode(curl_exec($curl));
			curl_close($curl);

			foreach ($rezult->results as $key => $value) {
				if (strtotime($value->created) < $last_created_at) {
					$stop_update = true;
					break;
				} else {
					$medias[] = $value;
				}
			}

			$offset += $limit;
		}

		krsort($medias);

		//

		if ($medias) {
			foreach ($medias as $key => $value) {
				$i = 0;
				$resolution = '';
				foreach ($value->qualities as $resol) {
					if($i == 0){
						$resolution = $resol->resolution;
					} else {
						$resolution = $resolution.','.$resol->resolution;
					}
					$i++;
				}

				$translation = Translation::where('id_VDB', $value->translation->id)
					->first();

				if (!$translation) {
					Translation::create([
						'id_VDB' => $value->translation->id,
						'title' => $value->translation->title
					]);

					$translation = Translation::where('id_VDB', $value->translation->id)
						->first();
				}

				// movie

				if ($value->content_object->content_type == 'movie') {
					$video = Video::where('id_VDB', $value->content_object->id)->where('tupe', 'movie')->first();

					if (!isset($video)) {
						$lastId = Video::create([
							'id_VDB' => $value->content_object->id, 
							'tupe' => $value->content_object->content_type,
							'name' => $value->content_object->orig_title, 
							'ru_name' => $value->content_object->ru_title, 
							'kinopoisk' => $value->content_object->kinopoisk_id,
							'imdb' => $value->content_object->imdb_id,
							'quality' => $value->source_quality.' '.$value->max_quality,
							'year' => '',
							'country' => '', 
							'description' => '',
							'img' => ''
						])->id;
					} else {
						$lastId = $video->id;
						Video::where('id', $video->id)->
							update([
								'id_VDB' => $value->content_object->id, 
								'tupe' => $value->content_object->content_type,
								'name' => $value->content_object->orig_title, 
								'ru_name' => $value->content_object->ru_title, 
								'kinopoisk' => $value->content_object->kinopoisk_id,
								'imdb' => $value->content_object->imdb_id,
								'quality' => $value->source_quality.' '.$value->max_quality
							]);
					}

					$file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
					if (!isset($file)) {
						File::create([
							'id_VDB' => $value->id,
							'id_parent' => $lastId,
							'path' => $value->path,
							'name' => $value->content_object->orig_title,
							'ru_name' => $value->content_object->ru_title,
							'season' => 0,
							'resolutions' => $resolution,
							'num' => 0,
							'translation_id' => $translation->id,
							'translation' => $value->translation->title,
							'sids' => 'VDB'
						])->id;
					}
				}

				// episode

				if ($value->content_object->content_type == 'episode') {
					$video = Video::where('id_VDB', $value->content_object->tv_series->id)->where('tupe', 'episode')->first();

					if (!isset($video)) {
						$lastId = Video::create([
							'id_VDB' => $value->content_object->tv_series->id, 
							'tupe' => $value->content_object->content_type,
							'name' => $value->content_object->tv_series->orig_title, 
							'ru_name' => $value->content_object->tv_series->ru_title,
							'kinopoisk' => $value->content_object->kinopoisk_id,
							'imdb' => $value->content_object->imdb_id,
							'quality' => $value->source_quality.' '.$value->max_quality,
							'year' => '',
							'country' => '', 
							'description' => '', 
							'img' => ''
						])->id;
					} else {
						$lastId = $video->id;
						Video::where('id', $video->id)->
							update([
								'id_VDB' => $value->content_object->tv_series->id, 
								'tupe' => $value->content_object->content_type,
								'name' => $value->content_object->tv_series->orig_title, 
								'ru_name' => $value->content_object->tv_series->ru_title,
								'kinopoisk' => $value->content_object->kinopoisk_id,
								'imdb' => $value->content_object->imdb_id,
								'quality' => $value->source_quality.' '.$value->max_quality
							]);
					}

					$file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
					if (!isset($file)) {
						File::create([
							'id_VDB' => $value->id,
							'id_parent' => $lastId,
							'path' => $value->path,
							'name' => $value->content_object->orig_title,
							'ru_name' => $value->content_object->ru_title,
							'season' => $value->content_object->season->num,
							'resolutions' => $resolution,
							'num' => $value->content_object->num,
							'translation_id' => $translation->id,
							'translation' => $value->translation->title,
							'sids' => 'VDB'
						])->id;
					}
				}

				//

				Videodb::where('method', 'sync')->update([
					'last_accepted_at' => $value->created
				]);
			}
		}
	}

	public function kinopoisk()
	{
		//
	}

}
