<?php

namespace App\Http\Controllers;

use App\PlayerEventStat;
use App\PlayerPayStat;
use App\Services\PayoutCalculationService;
use App\Services\PlayerEventStatsService;
use App\Services\TelegramNotificationService;
use App\UserTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    private PayoutCalculationService $payoutService;
    private PlayerEventStatsService $eventStatsService;
    private TelegramNotificationService $telegramService;

    public function __construct(
        PayoutCalculationService $payoutService,
        PlayerEventStatsService $eventStatsService,
        TelegramNotificationService $telegramService
    ) {
        $this->payoutService = $payoutService;
        $this->eventStatsService = $eventStatsService;
        $this->telegramService = $telegramService;
    }

    public function triggerDailyPayout(Request $request)
    {
        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));

        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException();
            }
            $date = $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use Y-m-d (e.g., 2025-12-15)',
                'date' => $request->input('date')
            ], 400);
        }

        PlayerPayStat::where('date', $date)->delete();
        UserTransaction::where('date', $date)->where('type', 'accrual')->delete();

        $result = $this->payoutService->calculateDailyPayouts($date);

        if ($result['success']) {
            $this->sendPayoutNotification($date);

            return response()->json([
                'success' => true,
                'message' => 'Payout calculation completed successfully',
                'date' => $date,
                'errors' => $result['errors'] ?? []
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Payout calculation failed',
                'date' => $date,
                'error' => $result['error']
            ], 500);
        }
    }

    private function sendPayoutNotification(string $date): void
    {
        try {
            $stats = PlayerPayStat::where('date', $date)
                ->selectRaw('SUM(counter) as total_views')
                ->selectRaw('SUM(counter * watch_price) as total_accruals')
                ->first();

            $this->telegramService->sendPayoutSummary($date, [
                'total_views' => $stats->total_views ?? 0,
                'total_accruals' => $stats->total_accruals ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send payout Telegram notification: " . $e->getMessage());
        }
    }

    public function triggerDailyEventStats(Request $request)
    {
        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));

        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException();
            }
            $date = $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use Y-m-d (e.g., 2025-12-15)',
                'date' => $request->input('date')
            ], 400);
        }

        PlayerEventStat::where('date', $date)->delete();

        $result = $this->eventStatsService->calculateDailyStats($date);

        if ($result['success']) {
            $this->sendEventStatsNotification($date, $result['processed_count'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => 'Player event stats calculation completed successfully',
                'date' => $date,
                'processed_count' => $result['processed_count'] ?? 0
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Player event stats calculation failed',
                'date' => $date,
                'error' => $result['error']
            ], 500);
        }
    }

    private function sendEventStatsNotification(string $date, int $processedCount): void
    {
        try {
            $geoStats = collect(\DB::table('player_event_stats as pes')
                ->leftJoin('geo_groups as g', 'pes.geo_group_id', '=', 'g.id')
                ->where('pes.date', $date)
                ->select('g.name')
                ->selectRaw('SUM(pes.counter) as total_events')
                ->groupBy('pes.geo_group_id', 'g.name')
                ->orderBy('total_events', 'desc')
                ->get())
                ->map(function ($row) {
                    return [
                        'name' => $row['name'] ?? 'Unknown',
                        'total_events' => (int) $row['total_events'],
                    ];
                })
                ->toArray();

            $this->telegramService->sendEventStatsSummary($date, $processedCount, $geoStats);
        } catch (\Exception $e) {
            Log::error("Failed to send event stats Telegram notification: " . $e->getMessage());
        }
    }
}