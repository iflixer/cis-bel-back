<?php

namespace App\Http\Controllers\api;

use App\Helpers\Cloudflare;
use App\IsoCountry;
use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use App\Right;
use App\LinkRight;

use App\Domain;
use App\LinkDomainTag;
use App\PlayerPay;
use App\Ad;
use App\Show;
use App\Seting;

use App\Helpers\Debug;
use DB;

class shows extends Controller{


    public $request;

    public function __construct(Request $request){
        $this->request = $request;
    }


    public function showsAd(){
        $messages = [];
        $response = [];

        $domain_name = $this->request->input('domain') ?? null;
        $tgc = $this->request->input('tgc') ?? null;
        if ($tgc) $domain_name = "@{$tgc}";

        if (empty($domain_name)) {
            abort(401, 'Domain or tgc not registered');
        }

        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event('vast_complete', $domain, $file_id);

        // TODO: remove this shit
        $dateNow = date("Y-m-d");

        $id = $this->request->input('id'); 
        if (empty($id)) {
            return;
        }
        $id_domain = Domain::select('id', 'show')->where('name', $domain_name)->first();

        if (!$id_domain) {
            $domain_name = substr($domain_name, strpos($domain_name, '.') + 1, strlen($domain_name));
            $id_domain = Domain::select('id', 'show')->where('name', $domain_name)->first();
        }

        $shows = Show::where('id_ad', $id)->where('id_domain', $id_domain->id)->whereBetween('updated_at', [ date('Y-m-d'), date('Y-m-d', strtotime("+1 days")) ])->first();

        $stats = json_decode($id_domain->show, true);
        if (isset($stats[$dateNow]['showads']))
            $stats[$dateNow]['showads'] += 1;
        else
            $stats[$dateNow]['showads'] = 1;
        $stats = json_encode($stats);
        Domain::where('name', $domain_name)->update(['show' => $stats ]);

        $ad = Ad::select('sale', 'procent')->where('id', $id)->first();
        $summ = ($ad->sale * ($ad->procent / 100)) / 1000;

        $idUser = Domain::select('id_parent')->where('name', $domain_name)->first();
        $scoreUser = User::select('score')->where('id', $idUser->id_parent)->first();

        User::where('id', $idUser->id_parent)->update(['score' => $scoreUser->score + $summ]);

        if( isset($shows) ){
            $type = 'update';
            Show::where('id', $shows->id)->update(['shows' => $shows->shows + 1]);
        }else{
            $type = 'create';
            Show::create([ 'shows' => 1, 'id_domain' => $id_domain->id, 'id_ad' => $id ]);
        }

        return ['data' => $response,'messages' => $messages];
    }


    public function getAds(){
        $messages = [];
        $response = [];

        $domain_name = $this->request->input('domain');
        $fullTime = $this->request->input('fullTime');

        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event('getads', $domain, $file_id);

        $userSetings = 30;

        $setings = [];
        $setingsData = Seting::whereIn('name', ['timeMin','timeMax'])->get()->toArray();
        foreach ($setingsData as $value) { $setings[$value['name']] = $value['value']; }


        // получение настроек временных промежутков
        $duration = ($setings['timeMax'] - (($setings['timeMax'] - $setings['timeMin']) / 100) * $userSetings) * 60;
        $countAds = round($fullTime / $duration, 0, PHP_ROUND_HALF_DOWN);


        $dataDomain = Domain::select('id', 'black_ad_on')->where('name', $domain_name)->first();

        $qweryAds = Ad::where('on', '1');
        if($dataDomain && !($dataDomain->black_ad_on)){ $qweryAds = $qweryAds->where('black_ad', '0');  }
        $ads = $qweryAds->get()->toArray();

        foreach ($ads as $key => $value) {
            $shows = Show::selectRaw('SUM(shows)')->where('id_ad', $value['id'])->first()->toArray()['SUM(shows)'];
            isset($shows) ? $ads[$key]['shows'] = (int)$shows : $ads[$key]['shows'] = 0;
        }


        // Разбивка объявлений на позиции и сортировка по просмотрам
        $start = array_filter($ads, function($item){ return $item['position'] == 'start'; });
        usort($start, function($a, $b){ if($a['shows']==$b['shows']){ return 0; } return($a['shows'] < $b['shows']) ? -1 : 1; });
        
        $center = array_filter($ads, function($item){ return $item['position'] == 'center'; });
        usort($center, function($a, $b){ if($a['shows']==$b['shows']){ return 0; } return($a['shows'] < $b['shows']) ? -1 : 1; });

        $end = array_filter($ads, function($item){ return $item['position'] == 'end'; });
        usort($end, function($a, $b){ if($a['shows']==$b['shows']){ return 0; } return($a['shows'] < $b['shows']) ? -1 : 1; });


        // Если рекламы меньше чем позиций, временные промежутки "растягиваются"
        if(count($center) < $countAds){
            $countAds = count($center);
            if($countAds != 0) $duration = round($fullTime / $countAds, 0, PHP_ROUND_HALF_DOWN);
        }

        // Обрезка массива с рекламой до числа позиций
        $center = array_slice($center, 0, $countAds);


        foreach ($center as $key => $value) {
            $center[$key]['time'] = ($key + 1) * $duration;
        }


        $response = [
            'start' => count($start) > 0 ? $start[0] : null,
            'center' => $center,
            'end' => count($end) > 0 ? $end[0] : null,

            'domain' => $domain_name,
            'fullTime' => $fullTime,
            'duration' => $duration,
            'countAds' => $countAds,
        ];

        return ['data' => $response,'messages' => $messages];
    }
    public function show(){
        $domain_name = $this->request->input('domain') ?? null;
        $tgc = $this->request->input('tgc') ?? null;
        if ($tgc) $domain_name = "@{$tgc}";

        if (empty($domain_name)) {
            abort(401, 'Domain or tgc not registered');
        }

        // DB::enableQueryLog();
        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event('play', $domain, $file_id);
        PlayerPay::save_event('pay', $domain, $file_id);
        // Debug::dump_queries(0);
        // die();

        // это пиздец. будем удалять
        $dateNow = date("Y-m-d");
        $domainStats = Domain::select('show')->where('name', $domain_name)->first();

        if (!$domainStats) {
            $domain_name = substr($domain_name, strpos($domain_name, '.') + 1, strlen($domain_name));
            $domainStats = Domain::select('show')->where('name', $domain_name)->first();
        }

        $stats = [];
        if($domainStats->show != ''){
            $stats = json_decode($domainStats->show, true);
        }
        if( isset($stats[$dateNow]) ){
            $stats[$dateNow]['show'] += 1;
        }else{
            $stats[$dateNow]['show'] = 1;
            $stats[$dateNow]['lowshow'] = 0;
            $stats[$dateNow]['showads'] = 0;
        }
        $stats = json_encode($stats);
        Domain::where('name', $domain_name)->update(['show' => $stats ]);
    }

    public function loaderror(){
        $domain_name = $this->request->input('domain') ?? null;
        $tgc = $this->request->input('tgc') ?? null;
        if ($tgc) $domain_name = "@{$tgc}";

        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event('loaderror', $domain, $file_id);
    }

    public function percent(){
        $percent = $this->request->input('percent') ?? null;
        if (empty($percent)) {
            return;
        }
        $domain_name = $this->request->input('domain') ?? null;
        $tgc = $this->request->input('tgc') ?? null;
        if ($tgc) $domain_name = "@{$tgc}";

        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event($percent, $domain, $file_id);
    }
    public function impression(){
        $domain_name = $this->request->input('domain') ?? null;
        $tgc = $this->request->input('tgc') ?? null;
        if ($tgc) $domain_name = "@{$tgc}";

        $domain = Domain::get_main_info($domain_name);
        $file_id = (int)$this->request->input('file_id');
        PlayerPay::save_event('impression', $domain, $file_id);
    }

    public function gaproxy() {
        $MEASUREMENT_ID = 'G-ECHML7LBXL';//getenv('GA4_MEASUREMENT_ID'); // напр. G-XXXXXXX
        $API_SECRET     = '6j36JeFwREujzMY-YzqejA';//getenv('GA4_API_SECRET');     // из Admin → Data streams → Measurement Protocol
        $TIMEOUT        = 3; // сек
        $ALLOWED_ORIGINS = ['https://nginx.cis-bel-back.orb.local','https://cdn0.cdnhub.help','https://cdn1.cdnhub.help','https://cdn2.cdnhub.help','https://cdn3.cdnhub.help'];

        header('Content-Type: text/plain; charset=UTF-8');

        // CORS (если нужно дергать из браузера)
        if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $ALLOWED_ORIGINS, true)) {
            header('Vary: Origin');
            // header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            // header('Access-Control-Allow-Credentials: true');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Headers: Content-Type');
            header('Access-Control-Allow-Methods: POST, OPTIONS');
            http_response_code(204);
            exit;
        }

        // Принимаем только POST с JSON
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        $body = file_get_contents('php://input');
        if (!$body || strlen($body) > 64 * 1024) { // защитимся от мусора
            http_response_code(400);
            echo "Bad Request";
            exit;
        }
        // Быстрая валидация JSON
        $json = json_decode($body, true);
        if (!is_array($json)) {
            http_response_code(400);
            echo "Invalid JSON";
            exit;
        }

        // Подставим client_ip в X-Forwarded-For (GA4 не принимает ip в теле)
        $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? null;

        // Собираем URL GA4 MP
        $gaUrl = sprintf(
            'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
            rawurlencode($MEASUREMENT_ID),
            rawurlencode($API_SECRET)
        );

        // Проксируем в GA
        $ch = curl_init($gaUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                isset($_SERVER['HTTP_USER_AGENT']) ? 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'] : null,
                $clientIp ? 'X-Forwarded-For: '.$clientIp : null, // GA4 может использовать для гео
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
            CURLOPT_TIMEOUT        => $TIMEOUT,
        ]);

        $respBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        // Ответ клиенту: 204 если всё ок
        if ($err) {
            http_response_code(502);
            echo "Upstream error";
            exit;
        }
        // GA4 при успехе чаще отвечает 204. Просто пробрасываем 204, чтобы не палиться.
        http_response_code($httpCode ?: 204);
        echo $respBody; // обычно пусто; можно вообще ничего не писать
    }


}