<?php

namespace App\Services;

use App\Video;

use Illuminate\Support\Facades\Http;
class OpenaiService
{
    public function parseOpenaiDescription($prompt)
    {
        $apiKey = config('openai.token');
        $ch = curl_init("https://api.openai.com/v1/chat/completions");

        $data = [
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role" => "system", "content" => "Ты — помощник, который пишет краткие описания фильмов без спойлеров. Пиши по-русски."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.7
            // "max_tokens" => 200
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            fwrite(STDERR, "OpenAI CURL ERROR: {$err}\n");
        } else if ($httpCode >= 200 && $httpCode < 300 && $resp) {
            $data = json_decode($resp, true);
            $text = $data['choices'][0]['message']['content'] ?? '';
            $text = trim($text);
            if ($text !== '') {
                // Немного нормализуем переносы строк (ровно 3 абзаца не насилуем, но чистим)
                $text = preg_replace("/[\r\n]{3,}/u", "\n\n", $text);
                return $text;
            }
        } else {
            fwrite(STDERR, "OpenAI HTTP {$httpCode}: {$resp}\n");
        }

        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text);
        $text = trim($text);
        return $text;   
    }



    public function updateVideoWithOpenaiData($videoId)
    {
        $video = Video::find($videoId);

        if (!$video) {
            return false;
        }

        if (!empty($video->description)) {
            return false;
        }

        $prompt = $this->buildPrompt($video->toArray());

        $description = $this->parseOpenaiDescription($prompt);

        //dd($description);

        if (empty($description)) {
            return false;
        }
        Video::where('id', $videoId)->update([
            'update_openai' => 1,
            'description'=> $description
        ]);
        return true;
    }

    public function updateMultipleVideos($limit)
    {
        $response = [];
        $videos = Video::where('update_openai', 0)
            ->where('description', '=', '')
            ->limit($limit)
            ->get();

        foreach ($videos as $video) {
            $response[] = ['id' => $video->id];
            $this->updateVideoWithOpenaiData($video->id);
        }

        return $response;
    }

    function buildPrompt(array $row): string {
        // Поля: id, ru_name, kinopoisk, imdb, year
        $parts = [];

        // Основное имя
        $title = trim((string)$row['ru_name']);
        if ($title !== '') {
            $parts[] = "Название (рус.): {$title}";
        }

        // Идентификаторы (добавляем только непустые)
        $kp = trim((string)($row['kinopoisk'] ?? ''));
        if ($kp !== '' && strtolower($kp) !== '0') {
            $parts[] = "Кинопоиск ID: {$kp}";
        }

        $imdb = trim((string)($row['imdb'] ?? ''));
        if ($imdb !== '' && strtolower($imdb) !== '0') {
            // допустим, там может быть tt1234567 или просто число
            $parts[] = "IMDB: {$imdb}";
        }

        // Год (не добавлять, если 0)
        $year = (int)($row['year'] ?? 0);
        if ($year > 0) {
            $parts[] = "Год: {$year}";
        }

        $context = implode("; ", $parts);

        // Сам запрос
        $ask = "Дай описание содержания фильма без спойлеров, простым текстом, в трёх абзацах, без HTML-разметки.";
        // Важное уточнение про язык и формат
        $constraints = "Пиши по-русски. Без списков, без заголовков, без кавычек вокруг текста. 3 абзаца.";

        $prompt = "{$ask}\n\nВот данные о фильме для однозначной идентификации:\n{$context}\n\n{$constraints}";
        return $prompt;
    }

}
