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
use App\Services\OpenaiService;

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
	protected $openaiService;

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
		$this->openaiService = new OpenaiService();

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
		set_time_limit(0);
		$limit = 100;
		$offset = 0;
		$debug_mysql = 0;
		$force_import_extra = false;
		$mode = 'fresh';
		$vdb_id = 0;
		if ($this->request->input('mode'))
        	$mode = $this->request->input('mode');
		if ($this->request->input('offset'))
        	$offset = (int) $this->request->input('offset');
		if ($this->request->input('limit'))
        	$limit = (int) $this->request->input('limit');
		if ($this->request->input('debug_mysql'))
        	$debug_mysql = $this->request->input('debug_mysql');
		if ($this->request->input('force_import_extra'))
        	$force_import_extra = true;
		if ($this->request->input('vdb_id')) {
        	$vdb_id = $this->request->input('vdb_id');
			$mode = 'single';
		}


		if ($debug_mysql) {
			DB::enableQueryLog();
		}

		$order = "created";

		echo "import start {$mode} {$order} {$offset} {$limit}\n";

		if ($mode == 'fresh') {
			$order = "-accepted";
			$videodb = Videodb::select('last_accepted_at')->where('method', 'sync')->first()->toArray();
			$last_accepted_at = strtotime($videodb['last_accepted_at']);
			echo "Last accepted at: $last_accepted_at\n";
		}

		$where_vdb = '';
		if (!empty($vdb_id)) {
			$where_vdb = "&content_object={$vdb_id}";
		}

		$stop_update = false;
		$medias = [];
		while (!$stop_update) {
			if (!empty($where_vdb)) {
				$stop_update = true;
			}
			$request_start_time = microtime(true);
			$u = "https://videodb.win/api/v1/medias?ordering={$order}&limit={$limit}&offset={$offset}{$where_vdb}";
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
			//echo "rezult count: " . count($rezult->results) . "\n";

			foreach ($rezult->results as $key => $value) {
				if (($mode=='fresh') && ( strtotime($value->accepted) < $last_accepted_at ) ) {
					$stop_update = true;
					echo "Stop update\n";
					break;
				} else {
					$medias[] = $value;
				}
			}
			if ($mode=='fresh') {
				$offset += $limit;
			} else {
				$stop_update = true;
			}
		}
		echo "medias count: " . count($medias) . " time: " . (microtime(true) - $request_start_time). "\n";

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

				$video = null;
				$content_type = $value->content_object->content_type;

				// movie
				if (in_array($content_type, ['movie','anime','show'])) {
					$video = Video::where('id_VDB', $value->content_object->id)->where('tupe', $content_type)->first();

					$attr = [
						'id_VDB' => $value->content_object->id, 
						'tupe' => $content_type,
						'name' => $value->content_object->orig_title, 
						'ru_name' => $value->content_object->ru_title,
						'kinopoisk' => $value->content_object->kinopoisk_id,
						'quality' => "{$value->source_quality} {$value->max_quality}",
					];
					if (empty($video)) {
						$video = new Video($attr);
						$video->save();// to get id
					} else {
						$video->fill($attr);
					}
					if (($force_import_extra || empty($video->update_kino)) && !empty($video->kinopoisk)) {
						$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
					}
					if (($force_import_extra || empty($video->update_tmdb)) && !empty($video->imdb)) {
						$this->tmdbService->updateVideoWithTmdbData($video);
						$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
						$this->fanartService->updateVideoWithFanartData($video);
					}
					
					if (($force_import_extra || empty($video->update_openai)) && empty($video->description)) {
						$this->openaiService->updateVideoWithOpenaiData($video);
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
				if (in_array($content_type, ['episode','animeepisode','showepisode'])) {
					if (!in_array($value->content_object->tv_series->id, $updated_serials_id_vdb)) {
						$video = Video::where('id_VDB', $value->content_object->tv_series->id)->where('tupe', $content_type)->first();
						$updated_serials_id_vdb[] = $value->content_object->tv_series->id;
						$attr = [
							'id_VDB' => $value->content_object->tv_series->id, 
							'tupe' => $content_type,
							'name' => $value->content_object->tv_series->orig_title, 
							'ru_name' => $value->content_object->tv_series->ru_title,
							'kinopoisk' => $value->content_object->kinopoisk_id,
							'quality' => "{$value->source_quality} {$value->max_quality}",
						];
						if (empty($video)) {
							$video = new Video($attr);
							$video->save();// to get id
						} else {
							$video->fill($attr);
						}
						if (($force_import_extra || empty($video->update_kino)) && !empty($video->kinopoisk)) {
							$this->kinoPoiskService->updateVideoWithKinoPoiskData($video);
						}
						if (($force_import_extra || empty($video->update_tmdb)) && !empty($video->imdb)) {
							$this->tmdbService->updateVideoWithTmdbData($video);
							$this->thetvdbService->updateVideoWithThetvdbIdByImdbId($video);
							$this->fanartService->updateVideoWithFanartData($video);
						}
						if (($force_import_extra || empty($video->update_openai)) && empty($video->description)) {
							$this->openaiService->updateVideoWithOpenaiData($video);
						}
						$video->save();
					} else {
						$video = Video::where('id_VDB', $value->content_object->tv_series->id)->where('tupe', $content_type)->first();
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

				$ss_count = Screenshot::where('id_file', $file->id)->count();
				if ($force_import_extra || empty($ss_count)) {
					if (!empty($video)) {
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
					} else {
						echo "video is empty for content-type {$content_type}\n";
					}
				}

				if ($mode=='fresh') {
					Videodb::where('method', 'sync')->update([
						'last_accepted_at' => $value->accepted
					]);
				}
			}
		} // if medias

		if ($debug_mysql) {
			Debug::dump_queries($start_time);
		}
		echo "import END {$mode} {$order} {$offset} {$limit}\n";


	} // function

	public function import_empty_posters_tmdb() {
		$start_time = microtime(true);
		set_time_limit(0);
		DB::enableQueryLog();

		$limit = $this->request->input('limit') ?: 10;

		echo "Start import posters from tmdb for 'no-poster'\n";
		$videos = Video::where('img', 'like', '%no-poster%')
			->whereNotNull('imdb')
			->orderBy('id_VDB')
			->limit($limit)
			->get();

		echo "Found videos:".count($videos). "\n";

		foreach ($videos as $video) {
			try {
				$this->tmdbService->updateVideoWithTmdbData($video);
				$video->save();
			} catch (Throwable $e) {
				echo "TMDB update failed for video {$video->id_VDB}: " . $e->getMessage() . "\n";
			}
		}
			Debug::dump_queries($start_time);
		echo "END\n";
	}


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
