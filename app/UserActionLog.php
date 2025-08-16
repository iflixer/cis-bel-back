<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'request_data',
        'http_method',
        'url',
        'response_status',
        'created_at'
    ];

    protected $casts = [
        'request_data' => 'array',
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction($userId, $action, $requestData, $httpMethod, $url, $responseStatus = null)
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'request_data' => $requestData,
            'http_method' => $httpMethod,
            'url' => $url,
            'response_status' => $responseStatus,
            'created_at' => new \DateTime()
        ]);
    }
}