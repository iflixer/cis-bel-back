<?php

namespace App\Services;

use App\IsoCountry;
use App\PlayerLocationLog;

class LocationTracker
{
    /**
     * @param int|null $videoId
     * @param string|null $domainName
     * @param string|null $countryCode
     */
    public static function logPlayerRequest($videoId = null, $domainName = null, $countryCode = null)
    {
        if (!$countryCode) {
            return;
        }

        $country = IsoCountry::where('iso_code', strtoupper($countryCode))->first();
        if (!$country) {
            return;
        }

        PlayerLocationLog::create([
            'country_id' => $country->id,
            'video_id' => $videoId,
            'domain_name' => $domainName
        ]);
    }

    /**
     * @return string|null
     */
    public static function getCountryFromHeaders()
    {
        return isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null;
    }

    /**
     * @param int|null $videoId
     * @param string|null $domainName
     */
    public static function logPlayerRequestFromHeaders($videoId = null, $domainName = null)
    {
        $countryCode = self::getCountryFromHeaders();
        self::logPlayerRequest($videoId, $domainName, $countryCode);
    }
}