<?php

namespace App\Services;

use App\PlayerPayStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutCalculationService
{
    private PriceService $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function calculateDailyPayouts($date)
    {
        $errors = [];

        Log::info("Starting payout calculation for date: {$date}");

        try {
            $logEntries = $this->getPlayerPayLogEntries($date);

            if (empty($logEntries)) {
                Log::info("No player pay log entries found for date: {$date}");
                return [
                    'success' => true,
                    'processed_count' => 0,
                    'message' => 'No entries to process'
                ];
            }

            foreach ($logEntries as $entry) {
                $this->processGroupedEntries($date, $entry);
            }


            Log::info("Payout calculation completed for date: {$date}");

            return [
                'success' => true,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            $error = "Critical error in payout calculation: " . $e->getMessage();
            Log::error($error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    protected function getPlayerPayLogEntries($date)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        return DB::table('player_pay_log')
            ->select('domain_id', 'domain_type_id', 'geo_group_id')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('domain_type_id', 'geo_group_id')
            ->get();
    }


    protected function processGroupedEntries($date, $entry)
    {
        $watchPrice = $this->priceService->getVideoPriceById($entry['geo_group_id'], $entry['domain_type_id']);

        PlayerPayStat::createOrUpdateStat(
            $date,
            $entry['domain_id'],
            $entry['geo_group_id'],
            $watchPrice,
            $entry['count']
        );

        return [
            'success' => true,
            'count' => $entry['count'],
            'price' => $watchPrice
        ];
    }
}