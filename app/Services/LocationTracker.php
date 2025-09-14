<?php

namespace App\Services;

use App\Domain;
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

        $domainId = null;
        if ($domainName) {
            $domain = Domain::where('name', $domainName)->first();
            $domainId = $domain ? $domain->id : null;
        }

        PlayerLocationLog::create([
            'country_id' => $country->id,
            'video_id' => $videoId,
            'domain_id' => $domainId
        ]);
    }

    /**
     * @return string|null
     */
    public static function getCountryFromHeaders()
    {
        return isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null;
    }

    public static function logPlayerRequestById($videoId = null, $domainId = null, $countryCode = null)
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
            'domain_id' => $domainId
        ]);
    }

    public static function logPlayerRequestFromHeadersById($videoId = null, $domainId = null)
    {
        $countryCode = self::getCountryFromHeaders();
        self::logPlayerRequestById($videoId, $domainId, $countryCode);
    }
}