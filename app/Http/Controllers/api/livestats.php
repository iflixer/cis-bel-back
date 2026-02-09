<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;

use App\User;
use App\Right;
use App\LinkRight;
use App\Domain;

class livestats extends Controller
{
    public $request;
    public $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        if ($request->input('account_key') != '') {
            $this->user = User::where('api_key', $request->input('account_key'))->first()->toArray();
        } else {
            $this->user = User::where('id', $request->userId)->first()->toArray();
        }

        $idRight = LinkRight::where('id_user', $this->user['id'])->first();
        $right = Right::where('id', $idRight->id_rights)->first()->toArray();

        foreach ($right as $key => $value) {
            if ($key != 'id') {
                $this->user[$key] = $value;
            }
        }
    }

    public function getDomains()
    {
        $domains = Domain::select('id', 'name')
            ->where('id_parent', $this->user['id'])
            ->get();

        return [
            'data' => [
                'domains' => $domains
            ],
            'messages' => []
        ];
    }

    public function getLive()
    {
        $domainId = $this->request->input('domain_id');

        $userDomainIds = Domain::where('id_parent', $this->user['id'])->pluck('id')->toArray();

        if (!in_array($domainId, $userDomainIds)) {
            return [
                'data' => [
                    'events' => [],
                    'total' => 0
                ],
                'messages' => ['Домен не найден или нет доступа']
            ];
        }

        $events = DB::select("
            SELECT
                ppl.created_at,
                INET6_NTOA(ppl.visitor_ip) as ip,
                d.name as domain_name,
                COALESCE(v.ru_name, v.name, f.ru_name, f.name, 'Неизвестное видео') as video_name
            FROM player_pay_log ppl
            LEFT JOIN files f ON ppl.file_id = f.id
            LEFT JOIN videos v ON f.id_parent = v.id
            LEFT JOIN domains d ON ppl.domain_id = d.id
            WHERE ppl.domain_id = ?
              AND ppl.event = 'play'
              AND ppl.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY ppl.created_at DESC
            LIMIT 500
        ", [$domainId]);

        return [
            'data' => [
                'events' => $events,
                'total' => count($events)
            ],
            'messages' => []
        ];
    }
}
