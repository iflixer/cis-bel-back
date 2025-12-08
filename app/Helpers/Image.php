<?php
namespace App\Helpers;

class Image
{
    public static function makeInternalImageURL($resizer_domain, $type, $id, $url)
    {
        if (empty($url) || empty($type) || empty($url)) {
            return '';
        }
        return "https://{$resizer_domain}/{$type}/".$id."/".md5($url);
    }

}





