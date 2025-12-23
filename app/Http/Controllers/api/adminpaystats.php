<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use DB;
use DateTime;
use DateInterval;
use DatePeriod;

use App\User;
use App\Right;
use App\LinkRight;
use App\Domain;

class adminpaystats extends Controller
{
    public $request;
    public $user;

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

        if (!in_array($this->user['name'], ['administrator', 'redactor'])) {
            return [
                'data' => [],
                'messages' => ['У вас нет доступа к этой статистике.']
            ];
        }

        $period = $this->request->input('period', 'yesterday');
        $customRange = $this->request->input('custom_range');
        $userId = $this->request->input('user_id');
        $domainId = $this->request->input('domain_id');
        $geoGroupId = $this->request->input('geo_group_id');

        $dateRange = $this->getDateRange($period, $customRange);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $response['users_list'] = $this->getUsersList();
        $response['domains_list'] = $this->getDomainsList($userId);
        $response['geo_groups_list'] = $this->getGeoGroupsList();

        $query = DB::table('player_pay_stats as pps')
            ->leftJoin('domains as d', 'pps.domain_id', '=', 'd.id')
            ->select(
                'd.name as domain_name',
                'd.id_parent as user_id',
                'pps.domain_id',
                'pps.date',
                DB::raw('SUM(pps.counter) as total_views'),
                DB::raw('SUM(pps.counter * pps.watch_price) / 1000 as total_revenue'),
                DB::raw('AVG(pps.watch_price) as avg_watch_price')
            )
            ->where('pps.watch_price', '>', 0)
            ->whereBetween('pps.date', [$startDate, $endDate]);

        if ($userId && $userId != 'all') {
            $query->where('d.id_parent', $userId);
        }

        if ($domainId && $domainId != 'all') {
            $query->where('pps.domain_id', $domainId);
        }

        if ($geoGroupId && $geoGroupId != 'all') {
            $query->where('pps.geo_group_id', $geoGroupId);
        }

        $timeSeriesData = (clone $query)
            ->groupBy('pps.date')
            ->orderBy('pps.date', 'asc')
            ->get();

        $response['time_series'] = $this->formatTimeSeries($timeSeriesData, $startDate, $endDate);

        $domainBreakdown = (clone $query)
            ->groupBy('pps.domain_id', 'd.name', 'd.id_parent')
            ->orderBy('total_views', 'desc')
            ->get();

        $response['domain_breakdown'] = [];
        $totalViews = 0;
        $totalRevenue = 0;

        foreach ($domainBreakdown as $domain) {
            $domainArray = is_object($domain) ? (array)$domain : $domain;
            if (!isset($domainArray['domain_id'])) {
                continue;
            }

            $domainGeoGroups = DB::table('player_pay_stats as pps')
                ->leftJoin('geo_groups as gg', 'pps.geo_group_id', '=', 'gg.id')
                ->select(
                    'gg.name as geo_group_name',
                    'pps.geo_group_id',
                    'pps.watch_price',
                    DB::raw('SUM(pps.counter) as views'),
                    DB::raw('SUM(pps.counter * pps.watch_price) / 1000 as revenue')
                )
                ->where('pps.domain_id', $domainArray['domain_id'])
                ->whereBetween('pps.date', [$startDate, $endDate])
                ->where('pps.watch_price', '>', 0)
                ->groupBy('pps.geo_group_id', 'gg.name', 'pps.watch_price')
                ->orderBy('views', 'desc')
                ->get();

            $geoGroups = [];
            foreach ($domainGeoGroups as $geo) {
                $geoArray = is_object($geo) ? (array)$geo : $geo;
                $geoGroups[] = [
                    'geo_group_name' => $geoArray['geo_group_name'] ?? 'Неизвестная группа',
                    'geo_group_id' => $geoArray['geo_group_id'] ?? 0,
                    'views' => (int)($geoArray['views'] ?? 0),
                    'revenue' => (int)($geoArray['revenue'] ?? 0),
                    'watch_price' => (int)($geoArray['watch_price'] ?? 0)
                ];
            }

            $userName = User::where('id', $domainArray['user_id'])->value('login') ?? 'Неизвестный';

            $response['domain_breakdown'][] = [
                'domain_name' => $domainArray['domain_name'] ?? 'Unknown',
                'domain_id' => $domainArray['domain_id'],
                'user_id' => $domainArray['user_id'] ?? 0,
                'user_name' => $userName,
                'total_views' => (int)($domainArray['total_views'] ?? 0),
                'total_revenue' => (int)($domainArray['total_revenue'] ?? 0),
                'avg_watch_price' => (int)($domainArray['avg_watch_price'] ?? 0),
                'geo_groups' => $geoGroups
            ];

            $totalViews += (int)($domainArray['total_views'] ?? 0);
            $totalRevenue += (int)($domainArray['total_revenue'] ?? 0);
        }

        $priceGroups = DB::table('player_pay_stats as pps')
            ->leftJoin('domains as d', 'pps.domain_id', '=', 'd.id')
            ->select(
                'pps.watch_price',
                DB::raw('SUM(pps.counter * pps.watch_price) / 1000 as total_revenue'),
                DB::raw('SUM(pps.counter) as total_views')
            )
            ->whereBetween('pps.date', [$startDate, $endDate]);

        if ($userId && $userId != 'all') {
            $priceGroups->where('d.id_parent', $userId);
        }

        if ($domainId && $domainId != 'all') {
            $priceGroups->where('pps.domain_id', $domainId);
        }

        if ($geoGroupId && $geoGroupId != 'all') {
            $priceGroups->where('pps.geo_group_id', $geoGroupId);
        }

        $priceGroupsData = $priceGroups
            ->groupBy('pps.watch_price')
            ->orderBy('pps.watch_price', 'asc')
            ->get();

        $priceGroupsArray = [];
        foreach ($priceGroupsData as $priceGroup) {
            $priceGroupArray = is_object($priceGroup) ? (array)$priceGroup : $priceGroup;
            $watchPrice = (int)($priceGroupArray['watch_price'] ?? 0);
            
            if ($watchPrice > 0) {
                $priceGroupsArray[] = [
                    'watch_price' => $watchPrice,
                    'total_revenue' => (int)($priceGroupArray['total_revenue'] ?? 0),
                    'total_views' => (int)($priceGroupArray['total_views'] ?? 0)
                ];
            }
        }

        $response['summary'] = [
            'total_views' => $totalViews,
            'total_revenue' => $totalRevenue,
            'price_groups' => $priceGroupsArray
        ];

        $response['available_periods'] = $this->getAvailablePeriods();
        $response['date_range'] = [
            'start' => $startDate,
            'end' => $endDate
        ];

        $response['event_stats'] = $this->getEventStats($startDate, $endDate, $userId, $domainId, $geoGroupId);
        $response['geo_group_load_stats'] = $this->getGeoGroupLoadStats($startDate, $endDate, $userId, $domainId);

        return [
            'data' => $response,
            'messages' => $messages
        ];
    }

    private function getGeoGroupLoadStats($startDate, $endDate, $userId = null, $domainId = null)
    {
        $query = DB::table('player_event_stats as pes')
            ->leftJoin('geo_groups as gg', 'pes.geo_group_id', '=', 'gg.id')
            ->leftJoin('domains as d', 'pes.domain_id', '=', 'd.id')
            ->select(
                'pes.date',
                'gg.name as geo_group_name',
                DB::raw('SUM(pes.counter) as total_loads')
            )
            ->where('pes.event_type', 'load')
            ->whereBetween('pes.date', [$startDate, $endDate])
            ->groupBy('pes.date', 'gg.name')
            ->orderBy('pes.date', 'asc');

        if ($userId && $userId != 'all') {
            $query->where('d.id_parent', $userId);
        }

        if ($domainId && $domainId != 'all') {
            $query->where('pes.domain_id', $domainId);
        }

        $stats = $query->get();

        $seriesData = [];
        foreach ($stats as $stat) {
            $statArray = is_object($stat) ? (array)$stat : $stat;
            $groupName = $statArray['geo_group_name'] ?? 'Unknown';
            if (!isset($seriesData[$groupName])) {
                $seriesData[$groupName] = [];
            }
            $seriesData[$groupName][$statArray['date']] = (int)$statArray['total_loads'];
        }

        $result = [];
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );
        $dateArray = iterator_to_array($period);

        foreach ($seriesData as $groupName => $data) {
            $series = [
                'name' => $groupName,
                'data' => []
            ];
            foreach ($dateArray as $date) {
                $dateStr = $date->format('Y-m-d');
                $timestamp = (int)($date->format('U') . '000');
                $series['data'][] = [$timestamp, $data[$dateStr] ?? 0];
            }
            $result[] = $series;
        }

        return $result;
    }

    private function getEventStats($startDate, $endDate, $userId = null, $domainId = null, $geoGroupId = null)
    {
        $eventTypes = ['load', 'play', 'p1', 'p25', 'p50', 'p75', 'p100'];

        $query = DB::table('player_event_stats as pes')
            ->leftJoin('domains as d', 'pes.domain_id', '=', 'd.id')
            ->select(
                'pes.event_type',
                'pes.date',
                DB::raw('SUM(pes.counter) as total_count')
            )
            ->whereBetween('pes.date', [$startDate, $endDate])
            ->whereIn('pes.event_type', $eventTypes);

        if ($userId && $userId != 'all') {
            $query->where('d.id_parent', $userId);
        }

        if ($domainId && $domainId != 'all') {
            $query->where('pes.domain_id', $domainId);
        }

        if ($geoGroupId && $geoGroupId != 'all') {
            $query->where('pes.geo_group_id', $geoGroupId);
        }

        $timeSeriesData = (clone $query)
            ->groupBy('pes.date', 'pes.event_type')
            ->orderBy('pes.date', 'asc')
            ->get();

        $summaryData = (clone $query)
            ->select('pes.event_type', DB::raw('SUM(pes.counter) as total_count'))
            ->groupBy('pes.event_type')
            ->get();

        $timeSeries = [];
        foreach ($eventTypes as $eventType) {
            $timeSeries[$eventType] = $this->formatEventTimeSeries($timeSeriesData, $eventType, $startDate, $endDate);
        }

        $summary = [];
        foreach ($eventTypes as $eventType) {
            $summary[$eventType] = 0;
        }
        foreach ($summaryData as $item) {
            $itemArray = is_object($item) ? (array)$item : $item;
            $summary[$itemArray['event_type']] = (int)($itemArray['total_count'] ?? 0);
        }

        $conversions = [
            'load_to_play' => $summary['load'] > 0 ? round(($summary['play'] / $summary['load']) * 100, 2) : 0,
            'play_to_p25' => $summary['play'] > 0 ? round(($summary['p25'] / $summary['play']) * 100, 2) : 0,
            'play_to_p50' => $summary['play'] > 0 ? round(($summary['p50'] / $summary['play']) * 100, 2) : 0,
            'play_to_p75' => $summary['play'] > 0 ? round(($summary['p75'] / $summary['play']) * 100, 2) : 0,
            'play_to_p100' => $summary['play'] > 0 ? round(($summary['p100'] / $summary['play']) * 100, 2) : 0,
        ];

        return [
            'time_series' => $timeSeries,
            'summary' => $summary,
            'conversions' => $conversions
        ];
    }

    private function formatEventTimeSeries($data, $eventType, $startDate, $endDate)
    {
        $series = [];
        $dataByDate = [];

        foreach ($data as $item) {
            $itemArray = is_object($item) ? (array)$item : $item;
            if ($itemArray['event_type'] == $eventType) {
                $dateKey = $itemArray['date'];
                $dataByDate[$dateKey] = (int)($itemArray['total_count'] ?? 0);
            }
        }

        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $timestamp = (int)($date->format('U') . '000');

            if (isset($dataByDate[$dateStr])) {
                $series[] = [$timestamp, $dataByDate[$dateStr]];
            } else {
                $series[] = [$timestamp, 0];
            }
        }

        return $series;
    }

    private function formatTimeSeries($data, $startDate, $endDate)
    {
        $views = [];
        $revenue = [];
        $dataByDate = [];

        foreach ($data as $item) {
            if (is_object($item)) {
                $dateKey = $item->date;
                $dataByDate[$dateKey] = [
                    'total_views' => $item->total_views,
                    'total_revenue' => $item->total_revenue
                ];
            } elseif (is_array($item)) {
                $dateKey = $item['date'];
                $dataByDate[$dateKey] = [
                    'total_views' => $item['total_views'],
                    'total_revenue' => $item['total_revenue']
                ];
            }
        }

        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $timestamp = (int)($date->format('U') . '000');

            if (isset($dataByDate[$dateStr])) {
                $views[] = [$timestamp, (int)$dataByDate[$dateStr]['total_views']];
                $revenue[] = [$timestamp, round((int)$dataByDate[$dateStr]['total_revenue'] / 100, 2)];
            } else {
                $views[] = [$timestamp, 0];
                $revenue[] = [$timestamp, 0];
            }
        }

        return [
            'views' => $views,
            'revenue' => $revenue
        ];
    }

    private function getDateRange($period = 'yesterday', $customRange = null)
    {
        $endDate = new DateTime();
        $startDate = new DateTime();

        switch ($period) {
            case 'yesterday':
                $startDate->modify('-1 day');
                $endDate->modify('-1 day');
                break;
            case '7days':
                $startDate->modify('-7 days');
                $endDate->modify('-1 day');
                break;
            case '30days':
                $startDate->modify('-30 days');
                $endDate->modify('-1 day');
                break;
            case 'current_month':
                $startDate->modify('first day of this month');
                $endDate->modify('last day of this month');
                break;
            case 'last_month':
                $startDate->modify('first day of last month');
                $endDate->modify('last day of last month');
                break;
            case 'custom':
                if ($customRange && isset($customRange['start']) && isset($customRange['end'])) {
                    $startDate = new DateTime($customRange['start']);
                    $endDate = new DateTime($customRange['end']);
                }
                break;
            default:
                $startDate->modify('-1 day');
                $endDate->modify('-1 day');
        }

        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ];
    }

    private function getUsersList()
    {
        $users = User::select('id', 'login')
            ->orderBy('login', 'asc')
            ->get();

        $usersList = [];
        foreach ($users as $user) {
            $usersList[] = [
                'id' => $user->id,
                'login' => $user->login,
                'score' => $user->getBalance() / 100
            ];
        }

        return $usersList;
    }

    private function getDomainsList($userId = null)
    {
        $query = Domain::select('id', 'name', 'id_parent')
            ->where('status', 1)
            ->orderBy('name', 'asc');

        if ($userId && $userId != 'all') {
            $query->where('id_parent', $userId);
        }

        $domains = $query->get()->toArray();

        $domainsList = [];
        foreach ($domains as $domain) {
            $domainsList[] = [
                'id' => $domain['id'],
                'name' => $domain['name'],
                'user_id' => $domain['id_parent']
            ];
        }

        return $domainsList;
    }

    private function getGeoGroupsList()
    {
        $geoGroups = DB::table('geo_groups')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $geoGroupsList = [];
        foreach ($geoGroups as $geoGroup) {
            $geoGroupArray = is_object($geoGroup) ? (array)$geoGroup : $geoGroup;
            $geoGroupsList[] = [
                'id' => $geoGroupArray['id'],
                'name' => $geoGroupArray['name']
            ];
        }

        return $geoGroupsList;
    }

    private function getAvailablePeriods()
    {
        return [
            [
                'title' => 'Вчера',
                'value' => 'yesterday'
            ],
            [
                'title' => 'Последние 7 дней',
                'value' => '7days'
            ],
            [
                'title' => 'Последние 30 дней',
                'value' => '30days'
            ],
            [
                'title' => 'Текущий месяц',
                'value' => 'current_month'
            ],
            [
                'title' => 'Прошлый месяц',
                'value' => 'last_month'
            ],
            [
                'title' => 'Выбрать период',
                'value' => 'custom'
            ]
        ];
    }
}
