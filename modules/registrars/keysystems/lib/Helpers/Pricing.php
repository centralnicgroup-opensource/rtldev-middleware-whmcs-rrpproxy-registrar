<?php

namespace WHMCS\Module\Registrar\Keysystems\Helpers;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Module\Registrar\Keysystems\Commands\QueryExchangeRates;

class Pricing
{
    /**
     * @var array<string, float>
     */
    private static $exchangeRates = [];

    /**
     * @param array<string, mixed> $params
     * @param float $price
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     * @throws Exception
     */
    public static function convertPrice(array $params, float $price, string $fromCurrency, string $toCurrency): float
    {
        return round($price * self::getCachedExchangeRate($params, $fromCurrency, $toCurrency));
    }

    /**
     * @param array<string, mixed> $params
     * @param string $from
     * @param string $to
     * @return float
     * @throws Exception
     */
    private static function getCachedExchangeRate(array $params, string $from, string $to): float
    {
        if ($from == $to) {
            return 1;
        }
        $key = "$from-$to";
        if (!isset(self::$exchangeRates[$key])) {
            $rates = new QueryExchangeRates($params, $from, $to);
            $rates->execute();
            self::$exchangeRates[$key] = (float) $rates->api->properties["RATE"][0];
        }
        return self::$exchangeRates[$key];
    }

    /**
     * @param array<string, mixed> $params
     * @return void
     */
    public static function syncFeatures(array $params): void
    {
        DB::table('tbldomainpricing AS p')
            ->join('mod_rrpproxy_zones AS z', DB::raw('CONCAT(".", z.zone)'), '=', 'p.extension')
            ->where('p.autoreg', 'keysystems')
            ->update([
                'p.eppcode' => DB::raw('`z`.`epp_required`')
            ]);

        if ($params['AutoDNSManagement']) {
            DB::table('tbldomainpricing')
                ->where('autoreg', 'keysystems')
                ->update(['dnsmanagement' => 1]);
        }
        if ($params['AutoEmailForwarding']) {
            DB::table('tbldomainpricing')
                ->where('autoreg', 'keysystems')
                ->update(['emailforwarding' => 1]);
        }
        if ($params['AutoIDProtection']) {
            DB::table('tbldomainpricing AS p')
                ->join('mod_rrpproxy_zones AS z', DB::raw('CONCAT(".", z.zone)'), '=', 'p.extension')
                ->where('p.autoreg', 'keysystems')
                ->update([
                    'p.idprotection' => DB::raw('`z`.`id_protection`')
                ]);
        }
    }
}
