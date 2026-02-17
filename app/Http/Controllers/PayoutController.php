<?php

namespace App\Http\Controllers;

use App\PlayerEventStat;
use App\PlayerPayStat;
use App\Services\PayoutCalculationService;
use App\Services\PlayerEventStatsService;
use App\Services\TelegramNotificationService;
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
        $from = $request->input('from');
        $to = $request->input('to');
        $date = $request->input('date');

        if ($date && ($from || $to)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot use "date" together with "from"/"to"',
            ], 400);
        }

        if (($from && !$to) || (!$from && $to)) {
            return response()->json([
                'success' => false,
                'message' => 'Both "from" and "to" are required for date range',
            ], 400);
        }

        if ($from && $to) {
            $fromDate = $this->parseDate($from);
            $toDate = $this->parseDate($to);
            if (!$fromDate || !$toDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Use Y-m-d (e.g., 2025-12-15)',
                ], 400);
            }
            if ($fromDate->gt($toDate)) {
                return response()->json([
                    'success' => false,
                    'message' => '"from" must be before or equal to "to"',
                ], 400);
            }

            return $this->processDateRange($fromDate, $toDate);
        }

        $date = $date ?: Carbon::yesterday()->format('Y-m-d');
        $parsedDate = $this->parseDate($date);
        if (!$parsedDate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use Y-m-d (e.g., 2025-12-15)',
                'date' => $date
            ], 400);
        }

        return $this->processSingleDate($parsedDate->format('Y-m-d'));
    }

    private function processDateRange(Carbon $fromDate, Carbon $toDate)
    {
        $results = [];
        $failedDates = [];
        $current = $fromDate->copy();

        while ($current->lte($toDate)) {
            $date = $current->format('Y-m-d');
            $result = $this->payoutService->calculateDailyPayouts($date);

            $results[$date] = $result['success'];
            if (!$result['success']) {
                $failedDates[] = $date;
            }

            $current->addDay();
        }

        $total = count($results);
        $failed = count($failedDates);

        return response()->json([
            'success' => $failed === 0,
            'message' => $failed === 0
                ? "All {$total} dates processed successfully"
                : "{$failed} of {$total} dates failed",
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
            'total' => $total,
            'failed_dates' => $failedDates,
        ], $failed === 0 ? 200 : 207);
    }

    private function processSingleDate(string $date)
    {
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

    private function parseDate(string $value)
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $value);
            if (!$parsed || $parsed->format('Y-m-d') !== $value) {
                return null;
            }
            return $parsed;
        } catch (\Exception $e) {
            return null;
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