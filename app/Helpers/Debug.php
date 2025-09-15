<?php
namespace App\Helpers;

use DB;


class Debug
{
    public static function dump_queries($start_time, $line_end = "\n")
    {
        $queries = DB::getQueryLog();
        foreach ($queries as $q) {
            $sql = $q['query'];
            foreach ($q['bindings'] as $binding) {
                $binding = is_numeric($binding) ? $binding : "'$binding'";
                $sql = preg_replace('/\?/', $binding, $sql, 1);
            }
            echo "[" . $q['time'] . " ms] $sql".$line_end;
        }
        $totalMysqlTime = array_sum(array_column($queries, 'time'));
		echo "totalMysqlTime ms: {$totalMysqlTime}\n";
		echo "totalTime: " . (microtime(true) - $start_time) . "\n";
    }
}
