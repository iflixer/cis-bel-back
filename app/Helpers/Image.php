<?php
namespace App\Helpers;

class Image
{
    public static function makeInternalImageURL($api_domain, $type, $id, $url)
    {
        if (empty($url) || empty($type) || empty($url)) {
            return '';
        }
        return "https://sss.{$api_domain}/{$type}/".$id."/".md5($url);
    }

}





