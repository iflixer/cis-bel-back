<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Throwable;

use App\LinkRight;
use App\Right;
use App\User;
use App\Video;
use App\Actor;
use App\Director;
use App\Screenshot;
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
use App\Services\KinoPoiskService;
use App\Services\TmdbService;
use App\Services\FanartService;
use App\Services\OpenaiService;
use App\Services\ThetvdbService;
use App\Services\R2Service;

use JonnyW\PhantomJs\Client;

use App\Helpers\Debug;


require_once __DIR__ . '/simplexlsxgen-master/src/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;
use function Sabre\Uri\split;

class TestController extends Controller
{

	protected $request;
	protected $loginVDB; 
	protected $passVDB; 
	protected $cdnhub_api_domain;
    protected $cdnhub_player_domain;

	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->loginVDB = Seting::where('name', 'loginVDB')->first()->toArray()['value'];
		$this->passVDB = Seting::where('name', 'passVDB')->first()->toArray()['value'];
		$this->cdnhub_api_domain = Seting::where('name', 'cdnhub_api_domain')->first()->toArray()['value'];
		$this->cdnhub_player_domain = Seting::where('name', 'cdnhub_player_domain')->first()->toArray()['value'];
	}

	public function api($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// curl_setopt($ch, CURLOPT_USERAGENT, 'DLE Module v1.1 for VideoDB https://cdnhub.pro');	
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_USERPWD, "{$this->loginVDB}:{$this->passVDB}");
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);

		return $data;
	}

	function translations()
	{
		// exit;

		$translations = $this->api('https://videodb.win/api/v1/translations/?format=json');

		print_r($translations);

		if ($translations) {
			foreach ($translations as $translation) {
				Translation::create([
					'id_VDB' => $translation['id'],
					'title' => $translation['title']
				]);
			}
		}
	}

	function setTranslationsInTableFiles()
	{
		exit;

		$files = File::get();

		if ($files) {
			foreach ($files as $file) {
				// print_r($file);
				// exit;

				if (!empty($file->translation) && empty($file->translation_id)) {
					$translation = Translation::where('title', $file->translation)->first();

					// print_r($translation);
					// exit;

					if (!empty($translation)) {
						File::where('id', $file->id)
						->update([
							'translation_id' => $translation->id
						]);
					}
				}
			}
		}

		// print_r($files);
	}

	public function episode($id)
	{
		$id = 145;

		echo 'good';
		exit;

		$video = Video::where('id', $id)->where('tupe', 'episode')->first();
		
		$exist = [];
		$files = File::where('id_parent', $id)->get();
		if ($files) {
			foreach ($files as $file)
				$exist[$file->id] = true;
		}

		// print_r($video);
		// print_r($files);

		// https://videodb.win/api/v1/tv-series/?id={$video->id_VDB}&format=json;
		// https://videodb.win/api/v1/tv-series/?kinopoisk_id=574688&format=json;

		$season = [];

		$url = "https://videodb.win/api/v1/tv-series/episodes/?tv_series={$video->id_VDB}&format=json";

		while (true) {
			$data = $this->api($url);
			$url = $data['next'];

			if ($data && $data['results']) {
				foreach ($data['results'] as $episode) {
					if (!isset($season[$episode['season']])) {
						$_data = $this->api("https://videodb.win/api/v1/tv-series/seasons/?id={$episode['season']}");
						$season[$episode['season']] = intval($_data['results'][0]['num']);
					}

					if ($episode['media']) {
						foreach ($episode['media'] as $media) {
							$file = File::where('id_VDB', $media['id'])->first();

							$resolution = '';
							foreach ($media['qualities'] as $quality) {
								$resolution .= ($resolution ? ',' : '') . $quality['resolution'];
							}

							if ($file) {
								unset($exist[$file->id]);

								File::where('id', $file->id)
									->update([
										'id_VDB' => $media['id'],
										'id_parent' => $video->id,
										'path' => $media['path'],
										'name' => $episode['orig_title'],
										'ru_name' => $episode['ru_title'],
										'season' => $season[$episode['season']],
										'resolutions' => $resolution,
										'num' => intval($episode['num']),
										'translation' => $media['translation']['title'],
										'sids' => 'VDB'
									]);
							} else {
								File::create([
									'id_VDB' => $media['id'],
									'id_parent' => $video->id,
									'path' => $media['path'],
									'name' => $episode['orig_title'],
									'ru_name' => $episode['ru_title'],
									'season' => $season[$episode['season']],
									'resolutions' => $resolution,
									'num' => intval($episode['num']),
									'translation' => $media['translation']['title'],
									'sids' => 'VDB'
								]);
							}
						}
					}
				}
			}

			if (!$data['next'])
				break;
		}

		// delete not found files ...

		// if (isset($exist[1]))

		print_r($exist);
	}

	public function update()
	{
		exit;

		$data = Videodb::select('last_accepted_at')->where('method', 'sync')->first()->toArray();
		print_r($data);
		$last_accepted_at = strtotime($data['last_accepted_at']);
		print_r($last_accepted_at);
	}

	public function player()
	{
		exit;

		$id = 145;

		$data = [];

		$video = Video::where('id', $id)->first()->toArray();
		// print_r($video);

		$files = File::where('id_parent', $id)
			->join('translations', 'files.translation_id', '=', 'translations.id')
			->orderBy('priority', 'desc')
			->orderBy('season', 'asc')
			->orderBy('num', 'asc')
			->get()
			->toArray();

		$resolutions = explode(',', $files[0]['resolutions']);
		sort($resolutions);

		$medias = [];

		$folder = '';
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		$date = date('YmdH', strtotime("+1 days"));
		$susuritiKey = Seting::where('name', 'keyWin')->first()->toArray()['value'];

		foreach ($resolutions as $resolution) {
			$file = parse_url($files[0]['path']);
			$date = date('YmdH', strtotime("+1 days"));
			$folder = $file['path'];

			$medias[] = "[{$resolution}]{$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolution}.mp4:hls:manifest.m3u8 or {$file['scheme']}://{$file['host']}{$folder}" . md5($folder.'-'.$ip.'-'.$date.'-'.$susuritiKey) . ":{$date}/{$resolution}.mp4";
		}

		$medias = implode(',', $medias);

		// print_r($medias);

		$data['medias'] = $medias;

		return view('player', $data);
	}

	public function restoreIndex()
	{
		exit;

		?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Restore</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="container">
		<form>
			<div class="row">
				<div class="col-auto mt-3">
					<label for="offset" class="form-label">Offset</label>
					<input type="text" class="form-control" id="offset" value="<?php echo isset($_GET['offset']) ? $_GET['offset'] : '0'; ?>">
				</div>
				<div class="col-auto mt-3">
					<label for="limit" class="form-label">Limit</label>
					<input type="text" class="form-control" id="limit" value="<?php echo isset($_GET['limit']) ? $_GET['limit'] : '99999'; ?>">
				</div>
				<div class="col-auto mt-3">
					<label for="limit" class="form-label">&nbsp;</label><br>
					<button id="start" type="button" class="btn btn-success">Начать</button>
					<button id="stop" type="button" class="btn btn-light" style="display:none">Остановить</button>
				</div>
		  </div>
		</form>
	</div>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		<!--

			var count = 0,
				offset = 0,
				limit = 0,
				restoreTimeout;

			function restore() {
				count++;
				offset++;
				
				$.ajax({
					url: '/test/restore/' + offset
				}).done(function(data) {
					$('#offset').val(offset);

					console.log('ID: ' + offset + ' (' + data + ')');

					if ($('#stop').is(':visible')) {
						if (count < limit) {
							restoreTimeout = setTimeout(restore, 0);
						} else {
							stop();
						}
					}
				});
			}

			function start() {
				$('#start').hide();
				$('#stop').show();
				$('#offset').prop('disabled', true);
				$('#limit').prop('disabled', true);

				offset = parseInt($('#offset').val());
				limit = parseInt($('#limit').val());

				restoreTimeout = setTimeout(restore, 0);
			}

			function stop() {
				$('#stop').hide();
				$('#start').show();
				$('#offset').prop('disabled', false);
				$('#limit').prop('disabled', false);

				clearTimeout(restoreTimeout);

				location.href = '/test/restore/index?offset=' + offset + '&limit=' + limit;
			}

			$('#start').click(function() {
				start();
			});

			$('#stop').click(function() {
				stop();
			});

		//-->
	</script>
</body>
</html>

		<?php
	}

	public function restore($id)
	{
		exit;

		set_time_limit(0);
		ini_set('memory_limit', '2048M');

		$video = Video::where('id', $id)->first()->toArray();

		if (!$video) {
			echo 'fail: not found video';
			exit;
		}

		/*if ($video['tupe'] == 'movie') {
			echo 'movie -> next';
			exit;
		}*/

		$files = File::where('id_parent', $id)->get()->toArray();

		if (!$files) {
			echo 'fail: not found files';
			exit;
		}

		$keyFiles = [];
		foreach ($files as $file) {
			if ($file['sids'] == 'VDB') {
				$keyFiles[$file['id_VDB']] = $file;
			}
		}

		// movie

		if ($video['tupe'] == 'movie') {
			$medias = [];

			$url = "https://videodb.win/api/v1/movies/{$video['id_VDB']}/?format=json";

			while (true) {
				$data = $this->api($url);
				$url = isset($data['next']) ? $data['next'] : null;

				if ($data && (isset($data['media']) && $data['media'])) {
					foreach ($data['media'] as $media) {
						$medias[] = $media;
					}
				}

				if (!$url)
					break;
			}

			if (!$medias) {
				echo 'fail: not found medias';
				exit;
			}

			// restore

			foreach ($medias as $media) {
				if (isset($keyFiles[$media['id']])) {
					if ($media['path'] == $keyFiles[$media['id']]['path']) {
						unset($keyFiles[$media['id']]);
						continue;
					} else {
						// update media path

						File::where('id', $keyFiles[$media['id']]['id'])->update(['path' => $media['path']]);

						unset($keyFiles[$media['id']]);
					}
				} else {
					// add new media

					$resolution = '';
					foreach ($media['qualities'] as $quality) {
						$resolution .= ($resolution ? ',' : '') . $quality['resolution'];
					}

					$translation = Translation::where('id_VDB', $media['translation']['id'])->first();

		            if (!$translation) {
		                Translation::create([
		                    'id_VDB' => $media['translation']['id'],
		                    'title' => $media['translation']['title']
		                ]);

		                $translation = Translation::where('id_VDB', $media['translation']['id'])
		                    ->first()->toArray();
		            }

					File::create([
						'id_VDB' => $media['id'],
						'id_parent' => $video['id'],
						'path' => $media['path'],
						'name' => $video['name'],
						'ru_name' => $video['ru_name'],
						'season' => 0,
						'resolutions' => $resolution,
						'num' => 0,
						'translation_id' => $translation['id'],
						'translation' => $media['translation']['title'],
						'sids' => 'VDB'
					]);
				}
			}

			// delete undefined medias

			foreach ($keyFiles as $keyFile) {
				File::where('id', $keyFile['id'])->delete();
			}

			echo 'movie';
		}

		// serial

		if ($video['tupe'] == 'episode') {
			$medias = [];
			$season = [];

			$url = "https://videodb.win/api/v1/tv-series/episodes/?tv_series={$video['id_VDB']}&format=json";

			while (true) {
				$data = $this->api($url);
				$url = $data['next'];

				if ($data && isset($data['results']) && $data['results']) {
					foreach ($data['results'] as $episode) {
						if (!isset($season[$episode['season']])) {
							$_data = $this->api("https://videodb.win/api/v1/tv-series/seasons/?id={$episode['season']}");
							$season[$episode['season']] = intval($_data['results'][0]['num']);
						}

						if ($episode['media']) {
							foreach ($episode['media'] as $media) {
								$medias[] = array_merge($media, [
									'seasonNum' => intval($season[$episode['season']]),
									'episodeNum' => intval($episode['num']),
									'episodeName' => $episode['orig_title'],
									'episodeRuName' => $episode['ru_title'],
								]);
							}
						}
					}
				}

				if (!$data['next'])
					break;
			}

			if (!$medias) {
				echo 'fail: not found medias';
				exit;
			}

			// restore

			foreach ($medias as $media) {
				if (isset($keyFiles[$media['id']])) {
					if ($media['path'] == $keyFiles[$media['id']]['path']) {
						File::where('id', $keyFiles[$media['id']]['id'])->update([
							'season' => $media['seasonNum'],
							'num' => $media['episodeNum']
						]);

						unset($keyFiles[$media['id']]);
						continue;
					} else {
						// update media path

						File::where('id', $keyFiles[$media['id']]['id'])->update([
							'path' => $media['path'],
							'season' => $media['seasonNum'],
							'num' => $media['episodeNum']
						]);

						unset($keyFiles[$media['id']]);
					}
				} else {
					// add new media

					$resolution = '';
					foreach ($media['qualities'] as $quality) {
						$resolution .= ($resolution ? ',' : '') . $quality['resolution'];
					}

					$translation = Translation::where('id_VDB', $media['translation']['id'])->first();

		            if (!$translation) {
		                Translation::create([
		                    'id_VDB' => $media['translation']['id'],
		                    'title' => $media['translation']['title']
		                ]);

		                $translation = Translation::where('id_VDB', $media['translation']['id'])
		                    ->first()->toArray();
		            }

					File::create([
						'id_VDB' => $media['id'],
						'id_parent' => $video['id'],
						'path' => $media['path'],
						'name' => $media['episodeName'],
						'ru_name' => $media['episodeRuName'],
						'season' => $media['seasonNum'],
						'resolutions' => $resolution,
						'num' => $media['episodeNum'],
						'translation_id' => $translation['id'],
						'translation' => $media['translation']['title'],
						'sids' => 'VDB'
					]);
				}
			}

			// delete undefined medias

			foreach ($keyFiles as $keyFile) {
				File::where('id', $keyFile['id'])->delete();
			}

			echo 'serial';
		}

		echo ' -> success';
	}

	public function export()
	{
		exit;

		ini_set('memory_limit', '512M');

		$limit = 10000;
		$end = 70446;

		$videos = [];

		$data = [
			[
				'<middle><center><b>id</b></center></middle>',
				'<middle><center><b>type</b></center></middle>',
				'<middle><center><b>title_orig</b></center></middle>',
				'<middle><center><b>title_rus</b></center></middle>',
				'<middle><center><b>year</b></center></middle>',
				'<middle><center><b>kinopoisk_id</b></center></middle>',
				'<middle><center><b>imdb_id</b></center></middle>'
			]
		];

		for ($offset = 0; $offset < $end; $offset+=$limit) {
			$videos = Video::select('id', 'tupe', 'name', 'ru_name', 'year', 'kinopoisk', 'imdb')
				->orderBy('id', 'asc')
				->offset($offset)
				->limit($limit)
				->get()
				->toArray();

			$count_videos = count($videos);

			for ($i = 0; $i < $count_videos; $i++) {
				$data[] = [
					$videos[$i]['id'],
					$videos[$i]['tupe'] == 'movie' ? 'movie' : 'serial',
					$videos[$i]['name'] ? $videos[$i]['name'] : '-',
					$videos[$i]['ru_name'] ? $videos[$i]['ru_name'] : '-',
					$videos[$i]['year'] ? $videos[$i]['year'] : '-',
					$videos[$i]['kinopoisk'] ? $videos[$i]['kinopoisk'] : '-',
					$videos[$i]['imdb'] ? $videos[$i]['imdb'] : '-'
				];

				unset($videos[$i]);
			}

			unset($count_videos);
		}

		$total = count($data) - 1;

		$xlsx = new SimpleXLSXGen;
		$xlsx->addSheet($data, "Videos ({$total})");
		unset($data);
		$xlsx->saveAs(__DIR__ . '/../../../videos.xlsx');

		echo "export: {$total}";
	}

	public function filterNetworkTraffic()
	{
		exit;

		$serverInfo = [
			'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : null,
			'SERVER_NAME' => isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null,
			'SERVER_PROTOCOL' => isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : null,

			'REQUEST_METHOD' => isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : null,
			'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : null,
			'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : null,

			'HTTP_X_REAL_IP' => isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] ? $_SERVER['HTTP_X_REAL_IP'] : null,
			'HTTP_X_FORWARDED_FOR' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null,
			'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : null,

			'HTTP_COOKIE' => isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE'] ? $_SERVER['HTTP_COOKIE'] : null,

			'HTTP_SEC_CH_UA' => isset($_SERVER['HTTP_SEC_CH_UA']) && $_SERVER['HTTP_SEC_CH_UA'] ? $_SERVER['HTTP_SEC_CH_UA'] : null,
			'HTTP_SEC_CH_UA_MOBILE' => isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ? $_SERVER['HTTP_SEC_CH_UA_MOBILE'] : null,
			'HTTP_SEC_CH_UA_PLATFORM' => isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) && $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ? $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] : null,

			'HTTP_UPGRADE_INSECURE_REQUESTS' => isset($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) && $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ? $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] : null,

			'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] ? $_SERVER['HTTP_ACCEPT'] : null,
			'HTTP_ACCEPT_ENCODING' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $_SERVER['HTTP_ACCEPT_ENCODING'] ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
			'HTTP_ACCEPT_LANGUAGE' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
		];

		$serverInfo = var_export($serverInfo, true);
		$serverInfo = str_replace('array (', '', $serverInfo);
		$serverInfo = rtrim($serverInfo, ")\r");

		$serverInfoData = date('d.m.Y H:i:s') . "\r{$serverInfo}\r\r";

		file_put_contents(__DIR__ . '/filterNetworkTraffic.log', $serverInfoData, LOCK_EX | FILE_APPEND);
	}

    public function importKinoPoisk(){
		$start_time = microtime(true);
        $limit = 2;
        $GLOBALS['debug_kinopoisk_import'] = 1;
        DB::enableQueryLog();
        $kinoPoiskService = new KinoPoiskService();
        $response = $kinoPoiskService->updateMultipleVideos($limit);
        Debug::dump_queries($start_time);
    }

	public function importKinoPoiskOnlyImdb(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_kinopoisk_import'] = 1;
        DB::enableQueryLog();
        $kinoPoiskService = new KinoPoiskService();
        $response = $kinoPoiskService->updateMultipleVideosOnlyImdb($limit);
		Debug::dump_queries($start_time);
    }

	public function importTmdb(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_tmdb_import'] = 1;
        DB::enableQueryLog();
        $tmdbService = new TmdbService();
        $response = $tmdbService->updateMultipleVideos($limit);
		Debug::dump_queries($start_time);
    }

	public function importFanart(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_tmdb_import'] = 1;
        DB::enableQueryLog();
        $fanartService = new FanartService();
        $response = $fanartService->updateMultipleVideos($limit);
		Debug::dump_queries($start_time);
    }

	public function importOpenai(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_tmdb_import'] = 1;
        DB::enableQueryLog();
        $openaiService = new OpenaiService();
        $response = $openaiService->updateMultipleVideos($limit);
        Debug::dump_queries($start_time);
    }

	public function importThetvdb(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_tmdb_import'] = 1;
        DB::enableQueryLog();
        $thetvdbService = new ThetvdbService();
        $response = $thetvdbService->updateMultipleVideosIds($limit);
        Debug::dump_queries($start_time);
    }

	public function sss($type, $id, $md5) {
		// $md5 - ewewe, wewe@500, wewee@500.jpg
		$md5 = explode("?", $md5)[0]; // remove any get-params
		$md5 = explode(".", $md5)[0]; // remove any extensions
		$md5_parts = explode('@', $md5);
		$md5_hash = $md5;
		$md5_resize = '';
		if (count($md5_parts) == 2) { 
			$md5_hash = $md5_parts[0];
			$md5_resize = $md5_parts[1];
		} 
        $contentType = '';
		$data = '';
		$response_code = 200;

		$r2Service = new R2Service();
		$storage_file_name = "cdnhub/sss/{$type}/{$id}/{$md5}";
		$storage_file_name_orig = "cdnhub/sss/{$type}/{$id}/{$md5_hash}";

		$orig_stored = false;
		// check if we already have original image in storage - no need to load it from original source
		$storage_object_orig = $r2Service->getFileFromStorage($storage_file_name_orig);
		if ($storage_object_orig['ok']) {
			$orig_stored = true;
			$data = $storage_object_orig['body'];
			$contentType = $storage_object_orig['content_type'];
		} 

		// not found in storage - load orig from original source
		if (empty($data)) {
			$remote_url = '';
			switch ($type) {
				case 'videos':
					$video = Video::find($id);
					if (empty($video)) return response('Video not found', 404);
					if (empty($remote_url) && md5($video->img) == $md5_hash) $remote_url = $video->img;
					if (empty($remote_url) && md5($video->backdrop) == $md5_hash) $remote_url = $video->backdrop;
					break;
				case 'actors':
					$actor = Actor::find($id);
					if (empty($actor)) return response('Actor not found', 404);
					if (empty($remote_url) && md5($actor->poster_url) == $md5_hash) $remote_url = $actor->poster_url;
					break;
				case 'directors':
					$director = Director::find($id);
					if (empty($director)) return response('Director not found', 404);
					if (empty($remote_url) && md5($director->poster_url) == $md5_hash) $remote_url = $director->poster_url;
					break;
				case 'screenshots':
					$ss = Screenshot::find($id);
					if (empty($ss)) return response('Screenshot not found', 404);
					if (empty($remote_url) && md5($ss->url) == $md5_hash) $remote_url = $ss->url;
					break;
				default:
					return response('type not found', 404);
			}

			if (empty($remote_url)) {
				return response('Md5 not found', 404);
			}

			$context = stream_context_create([
				'http' => [
					'method' => "GET",
					'timeout' => 20,
				]
			]);
			$data = file_get_contents($remote_url, false, $context);
			foreach ($http_response_header as $h) {
				if (stripos($h, 'Content-Type:') === 0) {
					$contentType = trim(substr($h, 13));
				}
				if (stripos($h, 'Location:') === 0) {
					if (strpos($h, 'no-poster.gif') !== false) { 
						// image not found! вернем заглушку но с кодом 404 чтобы кешировалась на небольшое время
						$response_code = 404;
					}
				}
			}
		}

		if (!empty($md5_resize)) {
			$w = (int)$md5_resize;
			$h = 0;
			if (starts_with($md5_resize, 'h')) {
				$w = 0;
				$h = (int)str_replace( 'h', '', $md5_resize);
			}
			if ($w>1000 || $h>1000) {
				return response('Resize error: width and height should be <= 1000', 400);
			}
			$img = new \Imagick();
			$img->readImageBlob($data);
			$origWidth = $img->getImageWidth();
			$origHeight = $img->getImageHeight();
			if ($w>0 && $w>$origWidth) $w = $origWidth;
			if ($h>0 && $h>$origHeight) $h = $origHeight;
			$img->resizeImage($w, $h, \Imagick::FILTER_LANCZOS, 1);
			$img->setImageFormat('webp');
			$data_resized = $img->getImageBlob();
		}

		if (!$orig_stored) { // upload original 
			try {
				$result = $r2Service->uploadFileToStorage($storage_file_name_orig, $contentType, $data);
			} catch (Throwable $e) {
				return response('R2 orig upload error: '.$e->getMessage(), 424);
			}
			if (empty($data_resized)) {
				$result->resolve(); // wait to finish upload if we will return original
			}
		}
		
		if (!empty($data_resized)) { // upload resized 
			try { 
				$result = $r2Service->uploadFileToStorage($storage_file_name, $contentType, $data_resized);
			} catch (Throwable $e) {
				return response('R2 resized upload error: '.$e->getMessage(), 424);
			}
			$result->resolve(); // wait to finish upload!
			return response($data_resized, $response_code)
				->header('X-B-Source', 'sss')
				->header('Content-Type', $contentType)
				->header('Cache-Control', 'public, immutable')
				->header('Content-Length', (string)strlen($data_resized))
				->header('ETag', $result->getETag() ?? '');
		}
		
		return response($data, $response_code)
			->header('X-B-Source', 'orig')
			->header('Content-Type', $contentType)
			->header('Cache-Control', 'public, immutable')
			->header('Content-Length', (string)strlen($data))
			->header('ETag', $result->getETag() ?? '');
    }

	public function importEmptyDescriptions(){
		$start_time = microtime(true);
        $limit = 10;
        $GLOBALS['debug_tmdb_import'] = 1;
        DB::enableQueryLog();
        $thetvdbService = new ThetvdbService();
        $response = $thetvdbService->updateMultipleVideosIds($limit);
        Debug::dump_queries($start_time);
    }

	// random movie URL for tests Andy
	public function randomMovie(){
		$maxId = Video::max('id');
		$randomId = rand(1, $maxId);

		$video = Video::where('id', '>=', $randomId)
				->orderBy('id')
				->first();

		// if ($this->request->input('kinopoisk')) {
		// 	$video = $video->whereNotNull('kinopoisk')->where('kinopoisk', '!=', '');
		// }
		// if ($this->request->input('imdb')) {
		// 	$video = $video->whereNotNull('imdb')->where('imdb', '!=', '');
		// }

		// $video = $video->first();

		if (empty($video)) {
			return response('Video not found', 404);
		}	

		$res = [
			'direct'=> "https://cdn0.{$this->cdnhub_player_domain}/show/{$video->id}",
			'kinopoisk'=> !empty($video->kinopoisk) ? "https://cdn0.{$this->cdnhub_player_domain}/show/kinopoisk/{$video->kinopoisk}" : null,
			'imdb'=> !empty($video->imdb) ? "https://cdn0.{$this->cdnhub_player_domain}/show/imdb/{$video->imdb}" : null,
			'id'=> $video->id,
			'name'=> $video->name,
			'ru_name'=> $video->ru_name,
		];
		return response()->json($res);
    }

	// metrics for prmetheus
	public function metrics(){

		$this->aggregatePlayerPayLogDeltas();

		$geoGroups = DB::table('geo_groups')->pluck('name', 'id');

		$lines = [];

		// helper для экранирования значений в лейблах Prometheus
		$escape = function (string $v): string {
			// экранируем \ и "
			return strtr($v, ["\\" => "\\\\", "\"" => "\\\""]);
		};

		// пробуем подключиться к БД
		try {
			DB::setFetchMode(\PDO::FETCH_OBJ);
			// стата по типам видео
			$byType = DB::select("
				SELECT tupe, COUNT(*) AS total
				FROM videos
				GROUP BY tupe WITH ROLLUP
			");

			// агрегаты по наполненности
			$agg = DB::selectOne("
				SELECT
				COUNT(*)                                                   AS total,
				SUM(CASE WHEN description IS NULL OR description = '' THEN 1 ELSE 0 END) AS missing_description,
				SUM(CASE WHEN `year` IS NULL OR `year` = 0 THEN 1 ELSE 0 END)            AS year_zero,
				SUM(CASE WHEN imdb IS NOT NULL AND imdb <> '' THEN 1 ELSE 0 END)         AS with_imdb,
				SUM(CASE WHEN kinopoisk IS NOT NULL AND kinopoisk <> '' THEN 1 ELSE 0 END)         AS with_kinopoisk
				FROM videos
			");

			// последняя запись
			$lastTs = DB::selectOne("
				SELECT UNIX_TIMESTAMP(MAX(created_at)) AS ts
				FROM videos
			");

			// db_up — если дошли до сюда, БД доступна
			$lines[] = "# HELP db_up MySQL connectivity (1=up)";
			$lines[] = "# TYPE db_up gauge";
			$lines[] = "db_up 1";

			// метрики по tupe
			$lines[] = "# HELP videos_total Number of videos by tupe (ROLLUP includes ALL)";
			$lines[] = "# TYPE videos_total gauge";
			foreach ($byType as $row) {
				$tupe  = $row->tupe ?: 'ALL';
				$lines[] = sprintf('videos_total{tupe="%s"} %d', $escape((string)$tupe), $row->total);
			}

			// агрегаты
			$total  = (int) ($agg->total ?? 0);
			$miss   = (int) ($agg->missing_description ?? 0);
			$year0  = (int) ($agg->year_zero ?? 0);
			$imdbOk = (int) ($agg->with_imdb ?? 0);
			$kinopoiskOk = (int) ($agg->with_kinopoisk ?? 0);

			$lines[] = "# HELP videos_count_total Total videos";
			$lines[] = "# TYPE videos_count_total gauge";
			$lines[] = "videos_count_total {$total}";

			$lines[] = "# HELP videos_missing_description_total Videos with empty or NULL description";
			$lines[] = "# TYPE videos_missing_description_total gauge";
			$lines[] = "videos_missing_description_total {$miss}";

			$lines[] = "# HELP videos_year_zero_total Videos with year=0 or NULL";
			$lines[] = "# TYPE videos_year_zero_total gauge";
			$lines[] = "videos_year_zero_total {$year0}";

			$lines[] = "# HELP videos_with_imdb_total Videos with non-empty IMDB ID";
			$lines[] = "# TYPE videos_with_imdb_total gauge";
			$lines[] = "videos_with_imdb_total {$imdbOk}";

			$lines[] = "# HELP videos_with_kinopoisk_total Videos with non-empty kinopoisk ID";
			$lines[] = "# TYPE videos_with_kinopoisk_total gauge";
			$lines[] = "videos_with_kinopoisk_total {$kinopoiskOk}";

			$ts = (int) ($lastTs->ts ?? 0)*1000;
			$lines[] = "# HELP videos_last_created_at_timestamp Unix time of the last created video";
			$lines[] = "# TYPE videos_last_created_at_timestamp gauge";
			$lines[] = "videos_last_created_at_timestamp {$ts}";


			// --- UU last 1h (по geo и, при желании, по event) ---
			$uuRows = DB::select("
				SELECT geo_group_id, COUNT(DISTINCT visitor_ip) AS uu
				FROM player_pay_log
				WHERE event='pay' AND created_at >= NOW() - INTERVAL 1 HOUR
				GROUP BY geo_group_id
			");
			$lines[] = "# HELP player_pay_log_unique_users_pay_last_1h Unique users (UU pay event) in the last 1 hour by geo group";
			$lines[] = "# TYPE player_pay_log_unique_users_pay_last_1h gauge";
			foreach ($uuRows as $r) {
				$geoId   = (int)($r->geo_group_id ?? 0);
				$geoName = $geoGroups[$geoId] ?? "Other";
				$uu      = (int)($r->uu ?? 0);
				$geoEsc  = str_replace(['\\', '"'], ['\\\\', '\\"'], $geoName);

				$lines[] = sprintf('player_pay_log_unique_users_pay_last_1h{geo_group="%s"} %d', $geoEsc, $uu);
			}


			$lines[] = "# HELP player_pay_log_total Cumulative player events (Prometheus counter)";
			$lines[] = "# TYPE player_pay_log_total counter";

			$counters = DB::select("SELECT event, geo_group_id, total FROM metrics_counters");
			foreach ($counters as $c) {
				$event = $c->event ?? 'unknown';
				$geoId = (int)$c->geo_group_id;
				$geoName = $geoGroups[$geoId] ?? "Other";
				$lines[] = sprintf(
					'player_pay_log_total{event="%s",geo_group="%s"} %d',
					$event,
					str_replace(['\\','"'], ['\\\\','\\"'], $geoName),
					(int)$c->total
				);
			}

			// событий в день по типу и гео
			$lines[] = "# HELP player_pay_log_today Number of player events today by geo_group and event type";
			$lines[] = "# TYPE player_pay_log_today gauge";
			$counters = DB::select("SELECT 
					event, 
					geo_group_id, 
					COUNT(*) AS total
				FROM player_pay_log
				WHERE created_at >= CURDATE()
				GROUP BY event, geo_group_id");

			foreach ($counters as $c) {
				$event = $c->event ?? 'unknown';
				$geoId = (int)$c->geo_group_id;
				$geoName = $geoGroups[$geoId] ?? "Other";
				$lines[] = sprintf(
					'player_pay_log_today{event="%s",geo_group="%s"} %d',
					$event,
					str_replace(['\\','"'], ['\\\\','\\"'], $geoName),
					(int)$c->total
				);
			}

			// Последний таймстамп (последняя запись)
			$last = DB::selectOne("SELECT UNIX_TIMESTAMP(MAX(created_at)) AS ts FROM player_pay_log");
			$ts = (int) ($last->ts ?? 0)*1000;
			$lines[] = "# HELP player_pay_log_last_timestamp Последнее зафиксированное событие";
			$lines[] = "# TYPE player_pay_log_last_timestamp gauge";
			$lines[] = "player_pay_log_last_timestamp {$ts}";


			$body = implode("\n", $lines) . "\n";
			return response($body, 200)->header('Content-Type', 'text/plain');
		} catch (Throwable $e) {
			// если упали — сообщим Prometheus-совместно
			$lines[] = "# HELP db_up MySQL connectivity (1=up)";
			$lines[] = "# TYPE db_up gauge";
			$lines[] = "db_up 0";
			$lines[] = "# HELP db_error_last Last DB error message (as info label)";
			$lines[] = "# TYPE db_error_last gauge";
			$msg = strtr($e->getMessage(), ["\\" => "\\\\", "\"" => "\\\"", "\n" => " "]);
			$lines[] = "db_error_last{message=\"{$msg}\"} 1";
			$body = implode("\n", $lines) . "\n";
			return response($body, 500)->header('Content-Type', 'text/plain');
		}
		
    }

	private function aggregatePlayerPayLogDeltas(): void
	{
		DB::setFetchMode(\PDO::FETCH_OBJ);
		DB::transaction(function () {
			// 1) заблокировать «курсор» (одна строка)
			$cursor = DB::table('metrics_cursor')->where('id', 1)->lockForUpdate()->first();
			$lastId = (int)($cursor->last_seen_id ?? 0);

			// 2) выбрать только новые строки
			$rows = DB::select("
				SELECT event, geo_group_id, COUNT(*) AS cnt, MAX(id) AS max_id
				FROM player_pay_log
				WHERE id > ?
				GROUP BY event, geo_group_id
			", [$lastId]);

			if (!$rows) {
				return; // нечего добавлять
			}

			// 3) инкрементально обновить счётчики
			// upsert с инкрементом total = total + VALUES(total)
			$values = [];
			foreach ($rows as $r) {
				$values[] = sprintf(
					"('%s', %d, %d)",
					addslashes($r->event ?? 'unknown'),
					(int)$r->geo_group_id,
					(int)$r->cnt
				);
			}
			$sql = "
				INSERT INTO metrics_counters (event, geo_group_id, total)
				VALUES ".implode(',', $values)."
				ON DUPLICATE KEY UPDATE total = total + VALUES(total),
										updated_at = CURRENT_TIMESTAMP
			";
			DB::statement($sql);

			// 4) продвинуть курсор на максимальный id из пачки
			$maxId = max(array_map(fn($r) => (int)$r->max_id, $rows));
			DB::table('metrics_cursor')->where('id', 1)->update([
				'last_seen_id' => $maxId,
				'updated_at'   => DB::raw('CURRENT_TIMESTAMP'),
			]);
		}, 5); // повторить до 5 раз при конфликте
	}

}