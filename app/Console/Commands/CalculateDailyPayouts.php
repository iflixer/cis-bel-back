<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayoutCalculationService;
use Carbon\Carbon;

class CalculateDailyPayouts extends Command
{
    protected $signature = 'payout:calculate-daily
                            {--date= : Calculate payouts for specific date (Y-m-d format). Defaults to yesterday.}
                            {--from= : Start date of range (Y-m-d format). Requires --to.}
                            {--to= : End date of range (Y-m-d format). Requires --from.}
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

        $dates = $this->resolveDates();
        if ($dates === false) {
            return 1;
        }

        $force = $this->option('force');
        $total = count($dates);

        if ($total > 1) {
            $this->info("Processing {$total} dates from {$dates[0]} to {$dates[$total - 1]}");
        }

        $failedDates = [];

        foreach ($dates as $i => $date) {
            if ($total > 1) {
                $num = $i + 1;
                $this->info("[{$num}/{$total}] Processing {$date}...");
            } else {
                $this->info("Starting payout calculation for date: {$date}");
            }

            if (!$force && $this->dataAlreadyExists($date)) {
                if ($total > 1) {
                    $this->warn("  Skipped (data exists, use --force to recalculate)");
                    continue;
                }
                if (!$this->confirm("Payout data already exists for {$date}. Do you want to recalculate?")) {
                    $this->info("Calculation cancelled.");
                    return 0;
                }
            }

            $result = $this->payoutService->calculateDailyPayouts($date);

            if ($result['success']) {
                if ($total === 1) {
                    $this->info("  Payout calculation completed successfully!");
                }

                if (!empty($result['errors'])) {
                    $this->warn("  Some errors occurred during processing:");
                    foreach ($result['errors'] as $error) {
                        $this->line("    • {$error}");
                    }
                }
            } else {
                $this->error("  Payout calculation failed for {$date}: {$result['error']}");
                $failedDates[] = $date;
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);

        if (!empty($failedDates)) {
            $this->error("Failed dates: " . implode(', ', $failedDates));
        }

        $this->info("Command completed in {$totalTime}s");

        return empty($failedDates) ? 0 : 1;
    }

    protected function resolveDates()
    {
        $date = $this->option('date');
        $from = $this->option('from');
        $to = $this->option('to');

        if ($date && ($from || $to)) {
            $this->error("--date cannot be used together with --from/--to");
            return false;
        }

        if (($from && !$to) || (!$from && $to)) {
            $this->error("Both --from and --to are required for date range");
            return false;
        }

        if ($from && $to) {
            $fromDate = $this->parseDate($from, '--from');
            $toDate = $this->parseDate($to, '--to');
            if (!$fromDate || !$toDate) {
                return false;
            }
            if ($fromDate->gt($toDate)) {
                $this->error("--from date must be before or equal to --to date");
                return false;
            }

            $dates = [];
            $current = $fromDate->copy();
            while ($current->lte($toDate)) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
            return $dates;
        }

        if ($date) {
            $parsed = $this->parseDate($date, '--date');
            if (!$parsed) {
                return false;
            }
            return [$parsed->format('Y-m-d')];
        }

        return [Carbon::yesterday()->format('Y-m-d')];
    }

    protected function parseDate($value, $optionName)
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $value);
            if (!$parsed || $parsed->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException();
            }
            return $parsed;
        } catch (\Exception $e) {
            $this->error("Invalid date format for {$optionName}. Please use Y-m-d format (e.g., 2025-09-22)");
            return null;
        }
    }

    protected function dataAlreadyExists($date)
    {
        return \App\PlayerPayStat::where('date', $date)->exists();
    }
}
