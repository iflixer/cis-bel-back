<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTransaction extends Model
{
    protected $table = 'user_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'date'
    ];

    protected $casts = [
        'amount' => 'integer',
        'date' => 'date',
        'user_id' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public static function getBalanceForUser($userId)
    {
        return self::where('user_id', $userId)->sum('amount');
    }

    public static function createAccrual($userId, $amount, $date = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'accrual',
            'amount' => abs($amount),
            'date' => $date ?: now()->toDateString()
        ]);
    }

    public static function createPayout($userId, $amount, $date = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'payout',
            'amount' => -abs($amount),
            'date' => $date ?: now()->toDateString()
        ]);
    }

    public static function createPenalty($userId, $amount, $date = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'penalty',
            'amount' => -abs($amount),
            'date' => $date ?: now()->toDateString()
        ]);
    }
}