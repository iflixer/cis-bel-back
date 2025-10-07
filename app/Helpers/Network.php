<?php
namespace App\Helpers;

class Network
{
    public static function allowOnlyInternal()
    {
        $allowed = ['10.', '127.0.0.1', '192.168.']; // подсети из которых можно
        $remote = request()->ip();

        $ok = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($remote, $prefix)) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            return response('Forbidden', 403);
        }
    }

}





