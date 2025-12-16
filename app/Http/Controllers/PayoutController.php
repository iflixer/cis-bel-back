<?php

namespace App\Http\Controllers;

use App\PlayerEventStat;
use App\PlayerPayStat;
use App\Services\PayoutCalculationService;
use App\Services\PlayerEventStatsService;
use App\UserTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    private PayoutCalculationService $payoutService;
    private PlayerEventStatsService $eventStatsService;

    public function __construct(PayoutCalculationService $payoutService, PlayerEventStatsService $eventStatsService)
    {
        $this->payoutService = $payoutService;
        $this->eventStatsService = $eventStatsService;
    }

    public function triggerDailyPayout(Request $request)
    {
        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));

        PlayerPayStat::where('date', $date)->delete();
        UserTransaction::where('date', $date)->where('type', 'accrual')->delete();

        $result = $this->payoutService->calculateDailyPayouts($date);

        if ($result['success']) {
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

    public function triggerDailyEventStats(Request $request)
    {
        $date = $request->input('date', Carbon::yesterday()->format('Y-m-d'));

        PlayerEventStat::where('date', $date)->delete();

        $result = $this->eventStatsService->calculateDailyStats($date);

        if ($result['success']) {
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
}