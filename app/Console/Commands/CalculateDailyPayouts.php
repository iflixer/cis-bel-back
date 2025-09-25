<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayoutCalculationService;
use Carbon\Carbon;

class CalculateDailyPayouts extends Command
{
    protected $signature = 'payout:calculate-daily 
                            {--date= : Calculate payouts for specific date (Y-m-d format). Defaults to yesterday.}
                            {--force : Force recalculation even if data already exists}';

    protected $description = 'Calculate daily payout statistics from player pay log';

    protected PayoutCalculationService $payoutService;

    public function __construct(PayoutCalculationService $payoutService)
    {
        parent::__construct();

        $this->payoutService = $payoutService;
    }

    public function handle()
    {
        $startTime = microtime(true);

        $date = $this->option('date');
        if (!$date) {
            $date = Carbon::yesterday()->format('Y-m-d');
        } else {
            try {
                Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Exception $e) {
                $this->error("Invalid date format. Please use Y-m-d format (e.g., 2025-09-22)");
                return 1;
            }
        }

        $force = $this->option('force');

        $this->info("Starting payout calculation for date: {$date}");

        if (!$force && $this->dataAlreadyExists($date)) {
            if (!$this->confirm("Payout data already exists for {$date}. Do you want to recalculate?")) {
                $this->info("Calculation cancelled.");
                return 0;
            }
        }

        $result = $this->payoutService->calculateDailyPayouts($date);

        if ($result['success']) {
            $this->info("  Payout calculation completed successfully!");

            if (!empty($result['errors'])) {
                $this->warn("Some errors occurred during processing:");
                foreach ($result['errors'] as $error) {
                    $this->line("  • {$error}");
                }
            }
        } else {
            $this->error("Payout calculation failed!");
            $this->error("Error: {$result['error']}");

            return 1;
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->info("Command completed in {$totalTime}s");

        return 0;
    }

    protected function dataAlreadyExists($date)
    {
        return \App\PlayerPayStat::where('date', $date)->exists();
    }
}