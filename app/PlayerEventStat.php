<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerEventStat extends Model
{
    protected $table = 'player_event_stats';
    protected $primaryKey = 'id';

    protected $fillable = [
        'date',
        'domain_id',
        'geo_group_id',
        'event_type',
        'counter'
    ];

    protected $casts = [
        'date' => 'date',
        'domain_id' => 'integer',
        'geo_group_id' => 'integer',
        'counter' => 'integer'
    ];

    public function domain()
    {
        return $this->belongsTo('App\Domain', 'domain_id');
    }

    public function geoGroup()
    {
        return $this->belongsTo('App\GeoGroup', 'geo_group_id');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    public function scopeByGeoGroup($query, $geoGroupId)
    {
        return $query->where('geo_group_id', $geoGroupId);
    }

    public function scopeByEvent($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public static function createOrUpdateStat($date, $domainId, $geoGroupId, $eventType, $counter)
    {
        return self::updateOrCreate(
            [
                'date' => $date,
                'domain_id' => $domainId,
                'geo_group_id' => $geoGroupId,
                'event_type' => $eventType
            ],
            [
                'counter' => $counter
            ]
        );
    }
}
