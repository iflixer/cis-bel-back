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
        'domain_type',
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

    public function scopeByDomainType($query, $domainType)
    {
        return $query->where('domain_type_id', $domainType);
    }

    public function scopeByDomainTypeId($query, $domainTypeId)
    {
        return $query->where('domain_type_id', $domainTypeId);
    }

    public static function getPrice($geoGroupId, $domainType)
    {
        $price = self::byGeoGroup($geoGroupId)->byDomainType($domainType)->first();
        return $price ? $price->price_cents : null;
    }

    public static function setPrice($geoGroupId, $domainType, $priceCents)
    {
        return self::updateOrCreate(
            ['geo_group_id' => $geoGroupId, 'domain_type' => $domainType],
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
        $price = self::byGeoGroup($geoGroupId)->byDomainTypeId($domainTypeId)->first();

        return $price ? $price->price_cents : null;
    }
}