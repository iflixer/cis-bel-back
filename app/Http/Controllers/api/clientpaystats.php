<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use DateTime;
use DateInterval;

use App\User;
use App\Right;
use App\LinkRight;
use App\Domain;

class clientpaystats extends Controller
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

        $period = $this->request->input('period', 'yesterday');
        $customRange = $this->request->input('custom_range');

        $dateRange = $this->getDateRange($period, $customRange);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $userDomains = $this->getUserDomains();

        if (empty($userDomains)) {
            return [
                'data' => [
                    'summary' => [
                        'total_views' => 0,
                        'total_revenue' => 0,
                        'avg_price_per_view' => 0
                    ],
                    'domain_breakdown' => [],
                    'available_periods' => $this->getAvailablePeriods()
                ],
            ];
        }

        $domainBreakdown = DB::table('player_pay_stats as pps')
            ->leftJoin('domains as d', 'pps.domain_id', '=', 'd.id')
            ->select(
                'd.name as domain_name',
                'pps.domain_id',
                DB::raw('SUM(pps.counter) as total_views'),
                DB::raw('SUM(pps.counter * pps.watch_price) / 1000 as total_revenue'),
                DB::raw('AVG(pps.watch_price) as watch_price')
            )
            ->whereIn('pps.domain_id', $userDomains)
            ->whereBetween('pps.date', [$startDate, $endDate])
            ->groupBy('pps.domain_id', 'd.name')
            ->orderBy('total_views', 'desc')
            ->get();

        $response['domain_breakdown'] = [];
        $totalViews = 0;
        $totalRevenue = 0;
        $totalWatchPriceSum = 0;
        $totalEntries = 0;

        foreach ($domainBreakdown as $domain) {
            $domainGeoGroups = DB::table('player_pay_stats as pps')
                ->leftJoin('geo_groups as gg', 'pps.geo_group_id', '=', 'gg.id')
                ->select(
                    'gg.name as geo_group_name',
                    'pps.geo_group_id',
                    'pps.watch_price',
                    DB::raw('SUM(pps.counter) as views'),
                    DB::raw('SUM(pps.counter * pps.watch_price) / 1000 as revenue')
                )
                ->where('pps.domain_id', $domain['domain_id'])
                ->whereBetween('pps.date', [$startDate, $endDate])
                ->groupBy('pps.geo_group_id', 'gg.name', 'pps.watch_price')
                ->orderBy('views', 'desc')
                ->get();

            $geoGroups = [];
            foreach ($domainGeoGroups as $geo) {
                $geoGroups[] = [
                    'geo_group_name' => $geo['geo_group_name'] ?? 'Неизвестная группа',
                    'geo_group_id' => $geo['geo_group_id'],
                    'views' => (int)$geo['views'],
                    'revenue' => (int)$geo['revenue'],
                    'watch_price' => (int)$geo['watch_price']
                ];
            }

            $response['domain_breakdown'][] = [
                'domain_name' => $domain['domain_name'],
                'domain_id' => $domain['domain_id'],
                'total_views' => (int)$domain['total_views'],
                'total_revenue' => (int)$domain['total_revenue'],
                'watch_price' => (int)$domain['watch_price'],
                'geo_groups' => $geoGroups
            ];

            $totalViews += (int)$domain['total_views'];
            $totalRevenue += (int)$domain['total_revenue'];
            $totalWatchPriceSum += (int)$domain['watch_price'];
            $totalEntries++;
        }

        $avgPricePerView = $totalEntries > 0 ? ($totalWatchPriceSum / $totalEntries) : 0;

        $response['summary'] = [
            'total_views' => $totalViews,
            'total_revenue' => $totalRevenue,
            'avg_price_per_view' => (int)$avgPricePerView
        ];

        $response['available_periods'] = $this->getAvailablePeriods();

        return [
            'data' => $response,
            'messages' => $messages
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

    private function getUserDomains()
    {
        return Domain::where('id_parent', $this->user['id'])->pluck('id')->toArray();
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
                'title' => 'Выбрать период',
                'value' => 'custom'
            ]
        ];
    }
}