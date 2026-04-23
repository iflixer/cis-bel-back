<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $fillable = [
        'id_parent', 
        'domain_type_id',
        'name',
        'status',
        'player',
        'show',
        'lowshow',
        'new_player',
        'black_ad_on'
    ];

    public function domainType()
    {
        return $this->belongsTo(DomainType::class, 'domain_type_id');
    }


    public static function get_main_info($domain_name, $columns=[]) {
        if (empty($columns)) $columns = ['id', 'id_parent', 'status', 'domain_type_id'];
        return self::select($columns)->whereIn('name', self::lookupCandidates($domain_name))->first();
    }

    public static function lookupCandidates($domain_name)
    {
        $ascii = self::normalizeName($domain_name);
        if ($ascii === null || $ascii === '' || (is_string($ascii) && isset($ascii[0]) && $ascii[0] === '@')) {
            return [$ascii];
        }

        $candidates = [$ascii];

        if (function_exists('idn_to_utf8') && strpos($ascii, 'xn--') !== false) {
            $flags = defined('IDNA_NONTRANSITIONAL_TO_UNICODE') ? IDNA_NONTRANSITIONAL_TO_UNICODE : 0;
            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
            $utf8 = @idn_to_utf8($ascii, $flags, $variant);
            if (is_string($utf8) && $utf8 !== '' && $utf8 !== $ascii) {
                $candidates[] = $utf8;
            }
        }

        return $candidates;
    }

    public static function normalizeName($domain_name)
    {
        if ($domain_name === null || $domain_name === '') {
            return $domain_name;
        }

        $value = trim((string) $domain_name);
        if ($value === '' || $value[0] === '@') {
            return $value;
        }

        $value = preg_replace('#^(http(s)?:)?//#i', '', $value);
        $slash = strpos($value, '/');
        if ($slash !== false) {
            $value = substr($value, 0, $slash);
        }

        $value = mb_strtolower($value, 'UTF-8');

        if (preg_match('/[^\x00-\x7F]/', $value) && function_exists('idn_to_ascii')) {
            $flags = defined('IDNA_NONTRANSITIONAL_TO_ASCII') ? IDNA_NONTRANSITIONAL_TO_ASCII : 0;
            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
            $ascii = @idn_to_ascii($value, $flags, $variant);
            if (is_string($ascii) && $ascii !== '') {
                return $ascii;
            }
        }

        return $value;
    }
}
