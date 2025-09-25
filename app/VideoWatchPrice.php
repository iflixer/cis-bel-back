<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VideoWatchPrice extends Model
{
    protected $table = 'video_watch_prices';
    protected $primaryKey = 'id';

    protected $fillable = [
        'geo_group_id',
        'domain_type_id',
        'price_cents'
    ];

    protected $casts = [
        'price_cents' => 'integer'
    ];

    public function geoGroup()
    {
        return $this->belongsTo('App\GeoGroup', 'geo_group_id');
    }

    public function domainType()
    {
        return $this->belongsTo('App\DomainType', 'domain_type_id');
    }

    public function scopeByGeoGroup($query, $geoGroupId)
    {
        return $query->where('geo_group_id', $geoGroupId);
    }


    public function scopeByDomainTypeId($query, $domainTypeId)
    {
        return $query->where('domain_type_id', $domainTypeId);
    }

    public static function getPrice($geoGroupId, $domainType)
    {
        $domainTypeRecord = \App\DomainType::where('value', $domainType)->first();
        if (!$domainTypeRecord) {
            return null;
        }

        $price = self::where('geo_group_id', $geoGroupId)
                    ->where('domain_type_id', $domainTypeRecord->id)
                    ->first();
        return $price ? $price->price_cents : null;
    }

    public static function setPrice($geoGroupId, $domainType, $priceCents)
    {
        $domainTypeRecord = \App\DomainType::where('value', $domainType)->first();
        if (!$domainTypeRecord) {
            throw new \Exception("Domain type '{$domainType}' not found");
        }

        return self::updateOrCreate(
            ['geo_group_id' => $geoGroupId, 'domain_type_id' => $domainTypeRecord->id],
            ['price_cents' => $priceCents]
        );
    }

    public static function setPriceById($geoGroupId, $domainTypeId, $priceCents)
    {
        return self::updateOrCreate(
            ['geo_group_id' => $geoGroupId, 'domain_type_id' => $domainTypeId],
            ['price_cents' => $priceCents]
        );
    }

    public static function getPriceById($geoGroupId, $domainTypeId)
    {
        $price = self::where('geo_group_id', $geoGroupId)
                    ->where('domain_type_id', $domainTypeId)
                    ->first();

        return $price ? $price->price_cents : null;
    }
}