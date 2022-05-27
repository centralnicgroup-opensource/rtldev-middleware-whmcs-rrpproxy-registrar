<?php

namespace WHMCS\Module\Registrar\RRPproxy;

use Illuminate\Database\Capsule\Manager as DB;

class Migrator
{
    /**
     * Migrates module config and domains from the WHMCS stock rrpproxy module to this one
     * @param array<string, mixed> $params
     * @return void
     */
    public static function migrate(array $params): void
    {
        $oldModule = 'rrpproxy';
        $newModule = 'keysystems';
        $oldConfig = DB::table('tblregistrars')
            ->where('registrar', $oldModule)
            ->pluck('value', 'setting');
        // This is needed for WHMCS v8 compatibility
        if ((int) $params['whmcsVersion'][0] >= 8) {
            $oldConfig = $oldConfig->toArray();
        }
        if (!empty($oldConfig)) {
            $oldConfig['TestPassword'] = $oldConfig['Password'];
            foreach ($oldConfig as $key => $val) {
                DB::table('tblregistrars')
                    ->where('registrar', $newModule)
                    ->where('setting', $key)
                    ->update(['value' => $val]);
            }
        }
        DB::table('tbldomains')->where('registrar', $oldModule)->update(['registrar' => $newModule]);
        DB::table('tbldomainpricing')->where('autoreg', $oldModule)->update(['autoreg' => $newModule]);
        DB::table('tblregistrars')->where('registrar', $oldModule)->delete();
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $reloadLink = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]?saved=true#keysystems";
        header("Location: $reloadLink");
    }
}
