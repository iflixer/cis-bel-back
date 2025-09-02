<?php

namespace App\Http\Controllers;

use App\Cdn;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CdnController extends Controller{
    public function __construct(Request $request){
        $this->request = $request;
    }

    public function netload(Request $request)
    {
        // Валидация через Validator
        $v = Validator::make($request->all(), [
            'node_name'             => 'required|string',
            'timestamp'             => 'required|numeric',
            'tx_bytes_per_sec'      => 'sometimes|numeric',
            'rx_bytes_per_sec'      => 'sometimes|numeric',
            'tx_bytes_per_sec_5m'   => 'sometimes|numeric',
            'rx_bytes_per_sec_5m'   => 'sometimes|numeric',
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        $data = $v->getData();

        $node = $data['node_name'];
        $parts = explode('-', $node);
        $id = (int) end($parts);
        if ($id==0) {
            return response()->json(['status' => 'error', 'error' => 'Invalid node ID']);
        }

        $data['host'] = "cdn$id.testme.wiki";
        $row = [
            'host' => $data['host'],
            'tx' => $data['tx_bytes_per_sec'] ?? null,
            'rx' => $data['rx_bytes_per_sec'] ?? null,
            'tx5m' => $data['tx_bytes_per_sec_5m'] ?? null,
            'rx5m' => $data['rx_bytes_per_sec_5m'] ?? null,
            'last_report' => $data['timestamp'],
        ];

        DB::enableQueryLog();

        Cdn::updateOrCreate(
            ['host' => $data['host']],
            $row
        );
        dd(DB::getQueryLog());

        return response()->json(['status' => 'ok']);
    }

}


