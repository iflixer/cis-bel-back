<?php

$t = env('R2_TOKEN');
$tf = env('R2_TOKEN_FILE');
if ($tf!="") {
    $t_ = file_get_contents($tf);
    if (!empty($t_)) {
        $t = $t_;
    }
} 

return [
    'token' => $t,
    'endpoint' => env('R2_ENDPOINT'),
    'key_id' => env('R2_KEY_ID'),
];
