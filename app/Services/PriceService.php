<?php

namespace App\Services;

use App\VideoWatchPrice;
use App\GeoGroup;
use App\DomainType;
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
                return (int)$priceCents;
            }

            return (int)env('BASE_VIDEO_PRICE_CENTS', 0);
        });
    }

    public function setVideoPriceById($geoGroupId, $domainTypeId, $priceCents)
    {
        $domainType = DomainType::find($domainTypeId);
        if (!$domainType) {
            throw new \Exception('Domain type not found');
        }

        $result = VideoWatchPrice::setPriceById($geoGroupId, $domainTypeId, $priceCents);
        $cacheKey = self::CACHE_KEY_PREFIX . $geoGroupId . '_' . $domainType->value;
        Cache::forget($cacheKey);

        return $result;
    }

    public function getPriceMatrix()
    {
        $geoGroups = GeoGroup::all(['id', 'name']);
        $domainTypes = DomainType::all(['name', 'value']);

        $prices = VideoWatchPrice::with(['geoGroup', 'domainType'])->get();
        $priceMatrix = [];

        $basePriceCents = (int)env('BASE_VIDEO_PRICE_CENTS', 0);
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

        foreach ($prices as $price) {
            $domainTypeName = $price->domainType->name;
            if ($domainTypeName && isset($priceMatrix[$price->geo_group_id]['prices'][$domainTypeName])) {
                $priceMatrix[$price->geo_group_id]['prices'][$domainTypeName]['price_cents'] = (int)$price->price_cents;
            }
        }

        return array_values($priceMatrix);
    }

    public function getDomainTypes()
    {
        return DomainType::all(['name as domain_type_name', 'value as domain_type'])
            ->toArray();
    }

    public function getGeoGroups()
    {
        return GeoGroup::all(['id', 'name'])->toArray();
    }

    public function clearPriceCache()
    {
        $geoGroups = GeoGroup::all(['id']);
        $domainTypes = DomainType::all(['value']);

        foreach ($geoGroups as $geoGroup) {
            foreach ($domainTypes as $domainType) {
                $cacheKey = self::CACHE_KEY_PREFIX . $geoGroup->id . '_' . $domainType->value;
                Cache::forget($cacheKey);
            }
        }
    }

    public function getBasePrice()
    {
        return (int)env('BASE_VIDEO_PRICE_CENTS', 0);
    }
}