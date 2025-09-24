<?php

namespace App\Services;

use App\VideoWatchPrice;
use App\GeoGroup;
use App\DomainTag;
use Cache;

class PriceService
{
    const CACHE_KEY_PREFIX = 'video_price_';
    const CACHE_DURATION = 3600;

    public function getVideoPrice($geoGroupId, $domainType)
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $geoGroupId . '_' . $domainType;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($geoGroupId, $domainType) {
            $priceCents = VideoWatchPrice::getPrice($geoGroupId, $domainType);
            
            if ($priceCents !== null) {
                return (int) $priceCents;
            }

            return (int) env('BASE_VIDEO_PRICE_CENTS', 0);
        });
    }

    public function setVideoPrice($geoGroupId, $domainType, $priceCents)
    {
        $result = VideoWatchPrice::setPrice($geoGroupId, $domainType, $priceCents);

        $cacheKey = self::CACHE_KEY_PREFIX . $geoGroupId . '_' . $domainType;
        Cache::forget($cacheKey);
        
        return $result;
    }

    public function getPriceMatrix()
    {
        $geoGroups = GeoGroup::all(['id', 'name']);
        $domainTypes = DomainTag::where('type', 'domain_type')->get(['name', 'value']);
        
        $prices = VideoWatchPrice::with('geoGroup')->get();
        $priceMatrix = [];

        $basePriceCents = (int) env('BASE_VIDEO_PRICE_CENTS', 0);
        foreach ($geoGroups as $geoGroup) {
            $priceMatrix[$geoGroup->id] = [
                'geo_group_id' => $geoGroup->id,
                'geo_group_name' => $geoGroup->name,
                'prices' => []
            ];
            
            foreach ($domainTypes as $domainType) {
                $priceMatrix[$geoGroup->id]['prices'][$domainType->name] = [
                    'domain_type_name' => $domainType->name,
                    'domain_type' => $domainType->value,
                    'price_cents' => $basePriceCents
                ];
            }
        }

        $domainTypeValueToName = [];
        foreach ($domainTypes as $domainType) {
            $domainTypeValueToName[$domainType->value] = $domainType->name;
        }

        foreach ($prices as $price) {
            $domainTypeName = $domainTypeValueToName[$price->domain_type] ?? null;
            if ($domainTypeName && isset($priceMatrix[$price->geo_group_id]['prices'][$domainTypeName])) {
                $priceMatrix[$price->geo_group_id]['prices'][$domainTypeName]['price_cents'] = (int) $price->price_cents;
            }
        }
        
        return array_values($priceMatrix);
    }

    public function getDomainTypes()
    {
        return DomainTag::where('type', 'domain_type')
            ->get(['name as domain_type_name', 'value as domain_type'])
            ->toArray();
    }

    public function getGeoGroups()
    {
        return GeoGroup::all(['id', 'name'])->toArray();
    }

    public function clearPriceCache()
    {
        $geoGroups = GeoGroup::all(['id']);
        $domainTypes = DomainTag::where('type', 'domain_type')->get(['value']);
        
        foreach ($geoGroups as $geoGroup) {
            foreach ($domainTypes as $domainType) {
                $cacheKey = self::CACHE_KEY_PREFIX . $geoGroup->id . '_' . $domainType->value;
                Cache::forget($cacheKey);
            }
        }
    }

    public function getBasePrice()
    {
        return (int) env('BASE_VIDEO_PRICE_CENTS', 0);
    }
}