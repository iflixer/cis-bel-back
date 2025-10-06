<?php

namespace App\Services;

use App\PlayerEventStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlayerEventStatsService
{
    protected static $trackedEvents = ['load', 'play', 'p1', 'p25', 'p50', 'p75', 'p100'];

    public function calculateDailyStats($date)
    {
        $errors = [];

        Log::info("Starting player event stats calculation for date: {$date}");

        try {
            $logEntries = $this->getPlayerEventLogEntries($date);

            if (empty($logEntries)) {
                Log::info("No player event log entries found for date: {$date}");
                return [
                    'success' => true,
                    'processed_count' => 0,
                    'message' => 'No entries to process'
                ];
            }

            foreach ($logEntries as $entry) {
                $this->processGroupedEntries($date, $entry);
            }

            Log::info("Player event stats calculation completed for date: {$date}");

            return [
                'success' => true,
                'processed_count' => count($logEntries),
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            $error = "Critical error in player event stats calculation: " . $e->getMessage();
            Log::error($error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    protected function getPlayerEventLogEntries($date)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        return DB::table('player_pay_log')
            ->select('domain_id', 'geo_group_id', 'event')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('event', self::$trackedEvents)
            ->groupBy('domain_id', 'geo_group_id', 'event')
            ->get();
    }

    protected function processGroupedEntries($date, $entry): array
    {
        PlayerEventStat::createOrUpdateStat(
            $date,
            $entry['domain_id'],
            $entry['geo_group_id'],
            $entry['event'],
            $entry['count']
        );

        return [
            'success' => true,
            'count' => $entry['count'],
            'event' => $entry['event']
        ];
    }
}
