<?php

namespace App\Services;

use App\Video;

use Illuminate\Support\Facades\Http;
class OpenaiService
{
    public function parseOpenaiDescription($name, $year)
    {
        $start_time = microtime(true);
        $apiKey = config('openai.token');
        $ch = curl_init("https://api.openai.com/v1/chat/completions");

        $data = [
            "model" => "gpt-5",
            "messages" => [
                ["role" => "system", "content" => "Ты эксперт в области кино, отвечаешь по-русски только текст без HTML и не добавляешь ничего кроме сути ответа. Ответ длиной 4-5 абзацев. Если не знаешь ответа - отвечаешь пустой строкой."],
                ["role" => "user", "content" => "ПРасскажи о чем фильм '{$name}' {$year} года"]
            ],
            "temperature" => 0.7,
            "max_tokens" => 200
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        if (!empty($GLOBALS['debug_tmdb_import'])) {
            echo "parseTmdbByImdbId API response: $response\n";
            echo "parseOpenaiDescription API duration: " . (microtime(true) - $start_time) . " seconds\n";
        }

        $result = json_decode($response, true);

        return $result['choices'][0]['message']['content'] ?? "";   
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

        $description = $this->parseOpenaiDescription($video->name, $video->year);

        dd($description);

        Video::where('id', $videoId)->update(['update_openai' => 1]);

        if (empty($description)) {
            return false;
        }

        $updateData = [];
        $updateData['description'] = $description;
        $updateData['update_openai'] = 2;
        Video::where('id', $videoId)->update($updateData);
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
            if (!$video->imdb) {
                continue;
            }
            $response[] = ['id' => $video->id];
            $this->updateVideoWithOpenaiData($video->id);
        }

        return $response;
    }
}
