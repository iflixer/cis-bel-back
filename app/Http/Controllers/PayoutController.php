<?php

namespace App\Http\Controllers;

use App\Services\PayoutCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    protected $payoutService;

    public function __construct(PayoutCalculationService $payoutService)
    {
        $this->payoutService = $payoutService;
    }

    public function triggerDailyPayout(Request $request)
    {
        $date = Carbon::yesterday()->format('Y-m-d');

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
}