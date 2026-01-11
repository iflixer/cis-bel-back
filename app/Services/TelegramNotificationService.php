<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private $botToken;
    private $chatId;
    private $enabled;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->chatId = config('telegram.chat_id');
        $this->enabled = config('telegram.enabled', false);
    }

    public function sendMessage(string $message): bool
    {
        if (!$this->enabled) {
            Log::info('Telegram notifications disabled, skipping message');
            return true;
        }

        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram bot_token or chat_id not configured');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("Telegram cURL error: {$curlError}");
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error("Telegram API error HTTP {$httpCode}: {$response}");
            return false;
        }

        $result = json_decode($response, true);
        if (!isset($result['ok']) || !$result['ok']) {
            Log::error("Telegram API returned error: {$response}");
            return false;
        }

        Log::info('Telegram message sent successfully');
        return true;
    }

    public function sendPayoutSummary(string $date, array $stats): bool
    {
        $totalViews = $stats['total_views'] ?? 0;
        $totalAccruals = $stats['total_accruals'] ?? 0;

        $message = "<b>Daily Payout Summary</b>\n";
        $message .= "Date: {$date}\n";
        $message .= "Total Views: " . number_format($totalViews) . "\n";
        $message .= "Total Accruals: $" . number_format($totalAccruals / 100);

        return $this->sendMessage($message);
    }

    public function sendEventStatsSummary(string $date, int $processedCount, array $geoStats = []): bool
    {
        $message = "<b>Daily Event Stats Summary</b>\n";
        $message .= "Date: {$date}\n";
        $message .= "Processed Groups: " . number_format($processedCount);

        if (!empty($geoStats)) {
            $totalEvents = array_sum(array_column($geoStats, 'total_events'));
            $message .= "\nTotal Events: " . number_format($totalEvents);
            $message .= "\n\n<b>Events by Region:</b>";

            foreach ($geoStats as $stat) {
                $name = $stat['name'];
                $events = number_format($stat['total_events']);
                $message .= "\n- {$name}: {$events}";
            }
        }

        return $this->sendMessage($message);
    }
}
