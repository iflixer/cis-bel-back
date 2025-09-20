<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use App\Services\FanartService;
use Illuminate\Http\Request;
use App\Http\Requests;
use Throwable;

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
use App\Screenshot;
use App\Link_country;
use App\Link_genre;

use App\Services\R2Service;
use App\Services\KinoPoiskService;
use App\Services\ThetvdbService;

use Mail;
use DB;

use App\Helpers\Debug;

// include $_SERVER['DOCUMENT_ROOT'].'/resources/simple_html_dom.php';

use JonnyW\PhantomJs\Client;

class CronjobController extends Controller
{

	public $request;
	protected $keyWin; 
	protected $loginVDB; // = 'kolobock'
	protected $passVDB; // = '5HxL2P2Yw1yq'
	protected $kinoPoiskService;
	protected $tmdbService;
	protected $thetvdbService;
	protected $fanartService;

	// protected $adress = 'https://api.kholobok.biz/show/';
	// protected $adress = 'https://cdnhub.help/show/';
	protected $adress = 'https://cdnhub.help/show/';

	protected $usesApi = "App\Http\Controllers\api\\";

	protected $cdnhub_api_domain;

	protected $r2Service;

	public function __construct(Request $request, KinoPoiskService $kinoPoiskService)
	{
		$this->request = $request;
		$this->kinoPoiskService = $kinoPoiskService;
		$this->tmdbService = new TmdbService();
		$this->thetvdbService = new ThetvdbService();
		$this->fanartService = new FanartService();

		// $this->loginVDB = config('videodb.login');
		// $this->passVDB = config('videodb.password');
		$this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
        $this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
        $this->keyWin = Seting::where('name', 'keyWin')->first()->toArray()['value'];
        $this->cdnhub_api_domain = Seting::where('name', 'cdnhub_api_domain')->first()->toArray()['value'];
		$this->r2Service = new R2Service();
	}

	public function videodb()
	{
		$start_time = microtime(true);
		DB::enableQueryLog();
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

		// video ids of already updated videos to prevent multiple updates of serials
		$updated_serials_id_vdb = [];

		// debug
		//array_splice($medias, 4);
		// var_dump($medias);

		if ($medias) {
			foreach ($medias as $key => $value) {
				$i = 0;
				$resolution = '';
				foreach ($value->qualities as $resol) {
					$resolution = ($i == 0) ? $resol->resolution : "{$resolution},{$resol->resolution}";
					$i++;
				}

				$translation = Translation::updateOrCreate(
					['id_VDB' => $value->translation->id],
					['title'=>$value->translation->title]
				);

				// movie
				if ($value->content_object->content_type == 'movie') {
					$video = Video::where('id_VDB', $value->content_object->id)->where('tupe', 'movie')->first();

					if (empty($video)) {
						$video = new Video([
							'id_VDB' => $value->content_object->id, 
							'tupe' => $value->content_object->content_type,
							'name' => $value->content_object->orig_title, 
							'ru_name' => $value->content_object->ru_title,
							'kinopoisk' => $value->content_object->kinopoisk_id,
							'imdb' => $value->content_object->imdb_id,
							'quality' => "{$value->source_quality} {$value->max_quality}",
						]);
						$video->save();// to get id
						if ($video->kinopoisk) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
						}
						if (!empty($video->imdb)) {
							$this->tmdbService->updateVideoWithTmdbData($video);
							$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
							$this->fanartService->updateVideoWithFanartData($video);
						}	
					} else {
						$video->fill([
								'id_VDB' => $value->content_object->id, 
								'tupe' => $value->content_object->content_type,
								'name' => $value->content_object->orig_title, 
								'ru_name' => $value->content_object->ru_title, 
								'kinopoisk' => $value->content_object->kinopoisk_id,
								'imdb' => $value->content_object->imdb_id,
								'quality' => "{$value->source_quality} {$value->max_quality}"
							]);
						//if (!$video->update_kino) {
							if (!empty($video->kinopoisk)) {
								$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
							}
							if (!empty($video->imdb)) {
								$this->tmdbService->updateVideoWithTmdbData($video);
								$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
								$this->fanartService->updateVideoWithFanartData($video);
							}	
						//}
					}

					$video->save();

					$file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
					if (empty($file)) {
						$file = File::create([
							'id_VDB' => $value->id,
							'id_parent' => $video->id,
							'path' => $value->path,
							'name' => $value->content_object->orig_title,
							'ru_name' => $value->content_object->ru_title,
							'season' => 0,
							'resolutions' => $resolution,
							'num' => 0,
							'translation_id' => $translation->id,
							'translation' => $value->translation->title,
							'sids' => 'VDB'
						]);
					}
				}

				// episode

				if ($value->content_object->content_type == 'episode') {
					if (!in_array($value->content_object->tv_series->id, $updated_serials_id_vdb)) {
						$video = Video::where('id_VDB', $value->content_object->tv_series->id)->where('tupe', 'episode')->first();
						$updated_serials_id_vdb[] = $value->content_object->tv_series->id;
						if (empty($video)) {
							$video = new Video([
								'id_VDB' => $value->content_object->tv_series->id, 
								'tupe' => $value->content_object->content_type,
								'name' => $value->content_object->tv_series->orig_title, 
								'ru_name' => $value->content_object->tv_series->ru_title,
								'kinopoisk' => $value->content_object->kinopoisk_id,
								'quality' => "{$value->source_quality} {$value->max_quality}",
							]);
							$video->save(); // to get id
							if (!empty($video->kinopoisk)) {
								$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
							}
							if (!empty($video->imdb)) {
								$this->tmdbService->updateVideoWithTmdbData($video);
								$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
								$this->fanartService->updateVideoWithFanartData($video);
							}	
						} else {
							$video->fill([
									'id_VDB' => $value->content_object->tv_series->id, 
									'tupe' => $value->content_object->content_type,
									'name' => $value->content_object->tv_series->orig_title, 
									'ru_name' => $value->content_object->tv_series->ru_title,
									'kinopoisk' => $value->content_object->kinopoisk_id,
									'quality' => "{$value->source_quality} {$value->max_quality}"
								]);
								
							//if (!$video->update_kino) {
								if (!empty($video->kinopoisk)) {
									$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
								}
								if (!empty($video->imdb)) {
									$this->tmdbService->updateVideoWithTmdbData($video);
									$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
									$this->fanartService->updateVideoWithFanartData($video);
								}
							//}
						}

						$video->save();
					}

					$file = File::where('id_VDB', $value->id)->where('sids', 'VDB')->first();
					if (empty($file)) {
						$file = File::create([
							'id_VDB' => $value->id,
							'id_parent' => $video->id,
							'path' => $value->path,
							'name' => $value->content_object->orig_title,
							'ru_name' => $value->content_object->ru_title,
							'season' => $value->content_object->season->num,
							'resolutions' => $resolution,
							'num' => $value->content_object->num,
							'translation_id' => $translation->id,
							'translation' => $value->translation->title,
							'sids' => 'VDB'
						]);
					}
				}

				// import screenshots
				$first_screenshot = '';
				if (!empty($file)) {
					if (!empty($value->screens)) {
						for($i=0; $i<count($value->screens); $i++) {
							$ss = Screenshot::updateOrCreate(
								[
									'id_file' => $file->id,
									'num' => $i
								],
								[
									'url' => $this->makeZeroCdnProtectedLink($value->screens[$i])
									]
							);
							if ($i==1) $first_screenshot = $ss->url;
						}
					}
				}

				// if we dont have any backdrop - use first screenshot as backdrop
				if (empty($video->backdrop) && !empty($first_screenshot)) {
					$video->backdrop = $first_screenshot;
				}

				// insane! if we dont have a poster - make it from backdrop!
				if (empty($video->img) && !empty($video->backdrop)) {
					$data = file_get_contents($video->backdrop, false);
					if (!empty($data)) {
						$img = new \Imagick();
						$img->readImageBlob($data);
						$origW = $img->getImageWidth();
						$origH = $img->getImageHeight();
						$ratio = 0.749;
						$cropH = $origH;
						$cropW = (int) round($cropH * $ratio);
						if ($cropW > $origW) {
							$cropW = $origW;
							$cropH = (int) round($cropW / $ratio);
						}
						$offsetX = (int)(($origW - $cropW) / 2);
						$offsetY = (int)(($origH - $cropH) / 2);
						$img->cropImage($cropW, $cropH, $offsetX, $offsetY);
						$img->setImagePage(0, 0, 0, 0); // сброс смещений
						$img->setImageFormat('webp');
						$data = $img->getImageBlob();
						$ok = true;
						$fname = md5(time());
						try {
							$storage_file_name_orig = "cdnhub/sss/videos/{$video->id}/{$fname}";
							$this->r2Service->uploadFileToStorage($storage_file_name_orig, 'image/webp', $data);
						} catch (Throwable $e) {
							echo 'ERROR saving to R2: '.$e->getMessage();
							$ok = false;
						}
						if ($ok) {
							$video->img = "https://sss.{$this->cdnhub_api_domain}/videos/{$video->id}/{$fname}";
						}
					}
				}

				$video->save();

				if ($is_update_last) {
					Videodb::where('method', 'sync')->update([
						'last_accepted_at' => $value->created
					]);
				}
			}
		} // if medias

		Debug::dump_queries($start_time);
		echo "END import start date: $start_date to $end_date\n";

	} // function

	private function makeZeroCdnProtectedLink($url): string {
		$host = parse_url($url, PHP_URL_HOST);
		$path = parse_url($url, PHP_URL_PATH);
		$date = '2055010101';
		$hash = md5("{$path}--{$date}-{$this->keyWin}");
		$signed = "https://{$host}/{$hash}:{$date}$path";
		return $signed;

	}

	public function kinopoisk()
	{
		//
	}

}
