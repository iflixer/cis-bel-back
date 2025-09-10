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

use App\Services\KinoPoiskService;

use Mail;
use DB;

// include $_SERVER['DOCUMENT_ROOT'].'/resources/simple_html_dom.php';

use JonnyW\PhantomJs\Client;

class CronjobController extends Controller
{

	public $request;
	protected $loginVDB; // = 'kolobock'
	protected $passVDB; // = '5HxL2P2Yw1yq'
	protected $kinoPoiskService;

	// protected $adress = 'https://api.kholobok.biz/show/';
	// protected $adress = 'https://cdnhub.help/show/';
	protected $adress = 'https://cdnhub.help/show/';

	protected $usesApi = "App\Http\Controllers\api\\";

	public function __construct(Request $request, KinoPoiskService $kinoPoiskService)
	{
		$this->request = $request;
		$this->kinoPoiskService = $kinoPoiskService;

		// $this->loginVDB = config('videodb.login');
		// $this->passVDB = config('videodb.password');
		$this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
        $this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
	}

	public function videodb()
	{
		set_time_limit(0);

		$start_date = null;
		$end_date = null;
		$vdb_date_lte = "";
		$is_update_last = false;
		// optional ?start_date=2024-03-04&end_date=2024-03-05
		if ($this->request->input('start_date'))
            $start_date = $this->request->input('start_date');
		if ($this->request->input('end_date'))
            $end_date = $this->request->input('end_date');

		echo "start date: $start_date\n";
		echo "end date: $end_date\n";

		if ($start_date && $end_date) {
			$last_created_at = strtotime($start_date);
			$vdb_date_lte = "&created__lte=" . date('Y-m-d', strtotime($end_date));
		} else {
			$videodb = Videodb::select('last_accepted_at')->where('method', 'sync')->first()->toArray();
			$last_created_at = strtotime($videodb['last_accepted_at']);
			echo "Last created at: $last_created_at\n";
			$is_update_last = true;
		}

		//

		$limit = 100;
		$offset = 0;

		$stop_update = false;

		$medias = [];


		while (!$stop_update) {
			$u = "https://videodb.win/api/v1/medias?ordering=-created&limit={$limit}&offset={$offset}{$vdb_date_lte}";
			echo "URL: $u\n";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($curl, CURLOPT_USERPWD, $this->loginVDB.':'.$this->passVDB);
			curl_setopt($curl, CURLOPT_URL, $u);
			$rezult = json_decode(curl_exec($curl));
			curl_close($curl);
			echo "rezult count: " . count($rezult->results) . "\n";

			foreach ($rezult->results as $key => $value) {
				if (strtotime($value->created) < $last_created_at) {
					$stop_update = true;
					echo "Stop update\n";
					break;
				} else {
					$medias[] = $value;
				}
			}

			$offset += $limit;
		}
		echo "medias count: " . count($medias) . "\n";

		krsort($medias);

		$created_files_total = [];
		$created_videos_total = [];
		$created_translations_total = [];

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
					$created_translations_total[] = $value->translation->title;

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
						$created_videos_total[] = $value->content_object->orig_title;
						if ($value->content_object->kinopoisk_id) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($lastId, true);
						}
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

						if ($value->content_object->kinopoisk_id && !$video->update_kino) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($lastId, true);
						}
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
						$created_files_total[] = $value->content_object->orig_title;
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
						$created_videos_total[] = $value->content_object->tv_series->orig_title;
						
						if ($value->content_object->kinopoisk_id) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($lastId, true);
						}
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
							
						if ($value->content_object->kinopoisk_id && !$video->update_kino) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($lastId, true);
						}
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
						$created_files_total[] = $value->content_object->orig_title;
					}
				}

				if ($is_update_last) {
					Videodb::where('method', 'sync')->update([
						'last_accepted_at' => $value->created
					]);
				}
			}
			echo "TOTAL videos created: " . count($created_videos_total) . "\n";
			echo "TOTAL files created: " . count($created_files_total) . "\n";
			echo "TOTAL translations created: " . count($created_translations_total) . "\n";
		} // if medias
	} // function

	public function kinopoisk()
	{
		//
	}

}
