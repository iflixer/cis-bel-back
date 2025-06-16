<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use DatePeriod;
use DateTime;
use DateInterval;

use App\User;
use App\Right;
use App\LinkRight;
use App\Domain;
use App\Tiket;
use App\Apilog;


class stats extends Controller
{

    public $request;
    public $user;

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


    protected function putDatasStat($stats, $colbec){
        if (!count($stats)) {
            return [];
        }
        
        $end = new DateTime($stats[ count($stats)-1 ]['date']);
        $end->modify('+1 day');

        $rez = [];
        $period = new DatePeriod( new DateTime($stats[0]['date']), new DateInterval('P1D'), $end );
        foreach($period as $date){
            $rez[] = $colbec($stats, $date);
        }
        return $rez;
    }


    protected function getUsersStat(){

        $right = Right::where('name', 'client')->first();
        $ids = array_map(function($item){
            return (int)$item['id_user'];
        }, LinkRight::select('id_user')->where('id_rights', $right->id)->get()->toArray() );

        $stats = User::selectRaw('count(*) as count, DATE(created_at) as date')
            ->whereIn('id', $ids)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()->toArray(); 

        return $this->putDatasStat($stats, function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });
    }


    protected function getStatsTikets(){

        $stats = [];

        $statsTikets = Tiket::selectRaw('count(*) as count, DATE(created_at) as date')
            ->where('tupe', 'tiket')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()->toArray();

        $statsDomains = Tiket::selectRaw('count(*) as count, DATE(created_at) as date')
            ->where('tupe', 'domain')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()->toArray();

        $statsFilms = Tiket::selectRaw('count(*) as count, DATE(created_at) as date')
            ->where('tupe', 'film')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()->toArray();

        $stats['tikets'] = $this->putDatasStat($statsTikets, function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });

        $stats['domains'] = $this->putDatasStat($statsDomains, function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });

        $stats['films'] = $this->putDatasStat($statsFilms, function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });

        return $stats;
    }

    private function getDatesByMonth($yearMonth)
    {
        $days = [];

        $start_date = "{$yearMonth}-01";
        $start_time = strtotime($start_date);

        $end_time = strtotime('+1 month', $start_time);

        for ($i = $start_time; $i < $end_time; $i += 86400) {
           $days[] = date('Y-m-d', $i);
        }

        return $days;
    }

    protected function getStatsShows()
    {
        $user_id = $this->request->input('user_id') && $this->request->input('user_id') != 'all' ? $this->request->input('user_id') : null;
        $domain = $this->request->input('domain') && $this->request->input('domain') != 'all' ? $this->request->input('domain') : null;
        $period = $this->request->input('period') ?: date('Y-m');
        // $user_id = 127;
        // $domain = 'vse-chasti-filmov.com';
        // $period = '2022-01';

        $periods = [];
        $periodsView = [];
        $periodsViewAds = [];

        $rezStats = [];

        if ($this->user['name'] == 'client') {
            $stats = Domain::select('name', 'show')->where('id_parent', $this->user['id']);

            if ($domain) {
                $stats->where('name', $domain);
            }

            $stats = $stats->get()->toArray();
        } else {
            $stats = Domain::select('name', 'show');

            if ($user_id) {
                $stats->where('id_parent', $user_id);
            }

            if ($domain) {
                $stats->where('name', $domain);
            }

            $stats = $stats->get()->toArray();
        }

        $statsStart = ["{$period}-01" => 0];
        $statsStartUniq = ["{$period}-01" => 0];
        $statsShow = ["{$period}-01" => 0];
        $statsLowShow = ["{$period}-01" => 0];
        $statsShowAds = ["{$period}-01" => 0];

        foreach ($stats as $value) {
            $_value_domain = $value['name'];
            if($value['show'] != ''){
                
                $domen = json_decode($value['show'], true);
                
                foreach ($domen as $key => $value) {

                    if (!in_array(date('Y-m', strtotime($key)), $periods)) {
                        $periods[] = date('Y-m', strtotime($key));
                    }

                    if (isset($periodsView[date('Y-m', strtotime($key))])) {
                        $periodsView[date('Y-m', strtotime($key))] += (isset($value['show']) ? $value['show'] : 0);
                    } else {
                        $periodsView[date('Y-m', strtotime($key))] = (isset($value['show']) ? $value['show'] : 0);
                    }
                    if (isset($periodsViewAds[date('Y-m', strtotime($key))])) {
                        $periodsViewAds[date('Y-m', strtotime($key))] += (isset($value['showads']) ? $value['showads'] : 0);
                    } else {
                        $periodsViewAds[date('Y-m', strtotime($key))] = (isset($value['showads']) ? $value['showads'] : 0);
                    }

                    if (strpos($key, $period) === false) {
                        continue;
                    }

                    if ($this->user['name'] != 'client') {
                        
                        if ($domain && $_value_domain != $domain) {

                            //

                        } else {

                            if( isset($statsStart[$key]) ){
                                $statsStart[$key] += (isset($value['start']) ? $value['start'] : 0);
                            }else{
                                $statsStart[$key] = (isset($value['start']) ? $value['start'] : 0);
                            }

                            if( isset($statsStartUniq[$key]) ){
                                $statsStartUniq[$key] += (isset($value['startuniq']) ? $value['startuniq'] : 0);
                            }else{
                                $statsStartUniq[$key] = (isset($value['startuniq']) ? $value['startuniq'] : 0);
                            }

                            if( isset($statsShow[$key]) ){
                                $statsShow[$key] += (isset($value['show']) ? $value['show'] : 0);
                            }else{
                                $statsShow[$key] = (isset($value['show']) ? $value['show'] : 0);
                            }

                            if( isset($statsLowShow[$key]) ){
                                $statsLowShow[$key] += (isset($value['lowshow']) ? $value['lowshow'] : 0);
                            }else{
                                $statsLowShow[$key] = (isset($value['lowshow']) ? $value['lowshow'] : 0);
                            }

                            if( isset($statsShowAds[$key]) ){
                                $statsShowAds[$key] += (isset($value['showads']) ? $value['showads'] : 0);
                            }else{
                                $statsShowAds[$key] = (isset($value['showads']) ? $value['showads'] : 0);
                            }

                        }

                    } else {

                        if( isset($statsStart[$key]) ){
                            $statsStart[$key] += (isset($value['start']) ? $value['start'] : 0);
                        }else{
                            $statsStart[$key] = (isset($value['start']) ? $value['start'] : 0);
                        }

                        if( isset($statsStartUniq[$key]) ){
                            $statsStartUniq[$key] += (isset($value['startuniq']) ? $value['startuniq'] : 0);
                        }else{
                            $statsStartUniq[$key] = (isset($value['startuniq']) ? $value['startuniq'] : 0);
                        }

                        if( isset($statsShow[$key]) ){
                            $statsShow[$key] += (isset($value['show']) ? $value['show'] : 0);
                        }else{
                            $statsShow[$key] = (isset($value['show']) ? $value['show'] : 0);
                        }

                        if( isset($statsLowShow[$key]) ){
                            $statsLowShow[$key] += (isset($value['lowshow']) ? $value['lowshow'] : 0);
                        }else{
                            $statsLowShow[$key] = (isset($value['lowshow']) ? $value['lowshow'] : 0);
                        }

                        if( isset($statsShowAds[$key]) ){
                            $statsShowAds[$key] += (isset($value['showads']) ? $value['showads'] : 0);
                        }else{
                            $statsShowAds[$key] = (isset($value['showads']) ? $value['showads'] : 0);
                        }

                    }

                }
            }
        }

        $dates = $this->getDatesByMonth($period);

        foreach ($dates as $_date) {
            if (!isset($statsStart[$_date])) {
                $statsStart[$_date] = 0;
            }
        }
        foreach ($dates as $_date) {
            if (!isset($statsStartUniq[$_date])) {
                $statsStartUniq[$_date] = 0;
            }
        }
        foreach ($dates as $_date) {
            if (!isset($statsShow[$_date])) {
                $statsShow[$_date] = 0;
            }
        }
        foreach ($dates as $_date) {
            if (!isset($statsLowShow[$_date])) {
                $statsLowShow[$_date] = 0;
            }
        }
        foreach ($dates as $_date) {
            if (!isset($statsShowAds[$_date])) {
                $statsShowAds[$_date] = 0;
            }
        }

        ksort($statsStart);
        ksort($statsStartUniq);
        ksort($statsShow);
        ksort($statsLowShow);
        ksort($statsShowAds);

        if ($statsStart) {
            $newStatsStart = [];
            foreach ($statsStart as $key => $value) {
                $newStatsStart[] = [ 'count' => $value, 'date' => $key];
            }
            $rezStats['start'] = $this->putDatasStat($newStatsStart, function($stats, $date){
                $count = 0;
                foreach($stats as $item){
                    if( $item['date'] === $date->format('Y-m-d') ){
                        $count = $item['count'];
                    }
                }
                return [ (int)($date->format('U')."000"), (int)$count ];
            });
        } else {
            $rezStats['start'] = [];
        }

        if ($statsStartUniq) {
            $newStatsStartUniq = [];
            foreach ($statsStartUniq as $key => $value) {
                $newStatsStartUniq[] = [ 'count' => $value, 'date' => $key];
            }
            $rezStats['startuniq'] = $this->putDatasStat($newStatsStartUniq, function($stats, $date){
                $count = 0;
                foreach($stats as $item){
                    if( $item['date'] === $date->format('Y-m-d') ){
                        $count = $item['count'];
                    }
                }
                return [ (int)($date->format('U')."000"), (int)$count ];
            });
        }
         else {
            $rezStats['startuniq'] = [];
        }

        if ($statsShow) {
            $newStatsShow = [];
            foreach ($statsShow as $key => $value) {
                $newStatsShow[] = [ 'count' => $value, 'date' => $key];
            }
            $rezStats['show'] = $this->putDatasStat($newStatsShow, function($stats, $date){
                $count = 0;
                foreach($stats as $item){
                    if( $item['date'] === $date->format('Y-m-d') ){
                        $count = $item['count'];
                    }
                }
                return [ (int)($date->format('U')."000"), (int)$count ];
            });
        } else {
            $rezStats['show'] = [];
        }

        if ($statsLowShow) {
            $newStatsLowShow = [];
            foreach ($statsLowShow as $key => $value) {
                $newStatsLowShow[] = [ 'count' => $value, 'date' => $key];
            }
            $rezStats['lowshow'] = $this->putDatasStat($newStatsLowShow, function($stats, $date){
                $count = 0;
                foreach($stats as $item){
                    if( $item['date'] === $date->format('Y-m-d') ){
                        $count = $item['count'];
                    }
                }
                return [ (int)($date->format('U')."000"), (int)$count ];
            });
        } else {
            $rezStats['lowshow'] = [];
        }

        if ($statsShowAds) {
            $newStatsShowAds = [];
            foreach ($statsShowAds as $key => $value) {
                $newStatsShowAds[] = [ 'count' => $value, 'date' => $key];
            }
            $rezStats['showads'] = $this->putDatasStat($newStatsShowAds, function($stats, $date){
                $count = 0;
                foreach($stats as $item){
                    if( $item['date'] === $date->format('Y-m-d') ){
                        $count = $item['count'];
                    }
                }
                return [ (int)($date->format('U')."000"), (int)$count ];
            });
        } else {
            $rezStats['showads'] = [];
        }

        if ($this->user['name'] == 'client') {
            $rezStats['start'] = [];
            $rezStats['startuniq'] = [];
            $rezStats['lowshow'] = [];
        }

        if (!in_array(date('Y-m'), $periods)) {
            $periods[] = date('Y-m');
        }

        $rezPeriods = [];
        foreach ($periods as $_date) {
            if ($_date == date('Y-m')) {
                $title = 'Текущий месяц';
            } elseif ($_date == date('Y-m', strtotime('-1 month', strtotime(date('Y-m'))))) {
                $title = 'Прошлый месяц';
            } else {
                $title = $_date;
            }

            $rezPeriods[] = [
                'title' => $title,
                'value' => $_date,
                'view' => (isset($periodsView[$_date]) ? $periodsView[$_date] : 0),
                'viewads' => (isset($periodsViewAds[$_date]) ? $periodsViewAds[$_date] : 0)
            ];
        }

        usort($rezPeriods, [$this, 'compareDates']);

        $rezStats['periods'] = $rezPeriods;

        return $rezStats;
    }

    private function compareDates($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);
        return ($t1 > $t2) ? -1 : 1;
    }


    protected function getSystemData(){
        $stats = [];

        $statsSystem = Apilog::get()->toArray();

        foreach ($statsSystem as $value) {
            
            $stats['reqwest'][] = [ 'count' => $value['count'], 'date' => $value['date'] ];
            $stats['loading'][] = [ 'count' => $value['loading'], 'date' => $value['date'] ];
        }

        $stats['reqwest'] = $this->putDatasStat($stats['reqwest'], function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });

        $stats['loading'] = $this->putDatasStat($stats['loading'], function($stats, $date){
            $count = 0;
            foreach($stats as $item){
                if( $item['date'] === $date->format('Y-m-d') ){
                    $count = $item['count'];
                }
            }
            return [ (int)($date->format('U')."000"), (int)$count ];
        });

        return $stats;
    }


    public function get(){

        $response = [];
        $messages = [];

        if ($this->user['name'] != 'client') {
            if ($this->request->input('loadStat')) {
                $response['users'] = [];
                $response['tikets'] = [
                    'tikets' => [],
                    'domains' => [],
                    'films' => [],
                ];
                $response['system'] = [
                    'reqwest' => [],
                    'loading' => [],
                ];
            } else {
                $response['users'] = $this->getUsersStat();
                $response['tikets'] = $this->getStatsTikets();
                $response['system'] = $this->getSystemData();
            }
        } else {
            $response['users'] = [];
            $response['tikets'] = [
                'tikets' => [],
                'domains' => [],
                'films' => [],
            ];
            $response['system'] = [
                'reqwest' => [],
                'loading' => [],
            ];
        }

        $response['shows'] = $this->getStatsShows();

        // get user domains

        $user_id = $this->request->input('user_id') && $this->request->input('user_id') != 'all' ? $this->request->input('user_id') : null;
        $userStatView = $this->request->input('userStatView') ? true : false;

        $adminView = false;

        if ($this->user['name'] == 'client') {
            $domains = Domain::select('name', 'show', 'id_parent')->where('id_parent', $this->user['id'])->get()->toArray();
            $response['userlist'] = [];
        } else {
            $adminView = true;

            if ($userStatView && $user_id) {
                $domains = Domain::select('name', 'show', 'id_parent')->where('id_parent', $user_id)->get()->toArray();
            } else {
                $domains = Domain::select('name', 'show', 'id_parent')->get()->toArray();
            }

            $users = User::select('id', 'login', 'score')->get()->toArray();
        }

        $allview = 0;
        $allviewads = 0;

        if ($this->user['name'] != 'client') {
            $usersScore = 0;
            $userListView = [];
            $userListViewAds = [];
        }

        if ($domains) {
            foreach ($domains as $key => $domain) {
                $startuniq = 0;
                $view = 0;
                $viewads = 0;

                if ($domain['show']) {
                    $show = json_decode($domain['show'], true);

                    if ($show) {
                        if (array_keys($show)[count($show) - 1] == date('Y-m-d')) {

                            $info = end($show);

                            if (isset($info['startuniq'])) {
                                $startuniq += $info['startuniq'];
                            }

                            if (isset($info['show'])) {
                                $view += $info['show'];
                            }

                            if (isset($info['showads'])) {
                                $viewads += $info['showads'];
                            }

                        }
                    }
                }

                if ($this->user['name'] != 'client') {

                    if (isset($userListView[$domain['id_parent']])) {
                        $userListView[$domain['id_parent']] += $view;
                    } else {
                        $userListView[$domain['id_parent']] = $view;
                    }
                    if (isset($userListViewAds[$domain['id_parent']])) {
                        $userListViewAds[$domain['id_parent']] += $viewads;
                    } else {
                        $userListViewAds[$domain['id_parent']] = $viewads;
                    }

                }

                $domains[$key]['title'] = $domain['name'];
                $domains[$key]['value'] = $domain['name'];
                $domains[$key]['startuniq'] = $startuniq;
                $domains[$key]['view'] = $view;
                $domains[$key]['viewads'] = $viewads;
                $id_parent = $domain['id_parent'];
                unset($domains[$key]['name']);
                unset($domains[$key]['show']);
                unset($domains[$key]['id_parent']);

                if ($user_id && $id_parent != $user_id) {
                    //
                } else {
                    $allview += $view;
                    $allviewads += $viewads;
                }

                if ($this->user['name'] != 'client') {
                    if (!$startuniq || ($user_id && $id_parent != $user_id)) {
                        unset($domains[$key]);
                    }
                }
            }

            $view = array_column($domains, 'view');
            array_multisort($view, SORT_DESC, SORT_NUMERIC, $domains);

            $response['userdomains'] = $domains;
        } else {
            $response['userdomains'] = [];
        }

        $response['userdomains'] = array_merge([
            [
                'title' => 'Все домены и каналы',
                'view' => $allview,
                'viewads' => $allviewads,
                'value' => 'all'
            ]
        ], $response['userdomains']);

        // get user list

        if ($this->user['name'] != 'client') {

            if ($users) {
                foreach ($users as $key => $user) {
                    $users[$key]['title'] = $user['login'];
                    $users[$key]['value'] = $user['login'];
                    $users[$key]['view'] = (isset($userListView[$user['id']]) ? $userListView[$user['id']] : 0);
                    $users[$key]['viewads'] = (isset($userListViewAds[$user['id']]) ? $userListViewAds[$user['id']] : 0);
                    unset($users[$key]['login']);

                    $usersScore += $user['score'];

                    if ($userStatView && $user['id'] == $user_id) {
                        //
                    } else {
                        if (!isset($userListView[$user['id']]) || !$userListView[$user['id']]) {
                            unset($users[$key]);
                        }
                    }
                }

                $view = array_column($users, 'view');
                array_multisort($view, SORT_DESC, SORT_NUMERIC, $users);

                $response['userlist'] = $users;
            } else {
                $response['userlist'] = [];
            }

            if (!$userStatView) {
                $response['userlist'] = array_merge([
                    [
                        'title' => 'Все пользователи',
                        'view' => $allview,
                        'viewads' => $allviewads,
                        'score' => $usersScore,
                        'value' => 'all'
                    ]
                ], $response['userlist']);
            }

        } else {
            $response['userlist'] = [];
        }
        
        return ['data' => $response,'messages' => $messages];
    }
}