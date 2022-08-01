<?php

namespace WHMCS\Module\Registrar\Keysystems;

class Updater
{
    public static function check(): int
    {
        $data = file_get_contents('https://raw.githubusercontent.com/rrpproxy/whmcs-rrpproxy-registrar/master/release.json');
        if (!$data) {
            return -1;
        }
        $json = json_decode($data);
        if (version_compare(RRPPROXY_VERSION, $json->version, '<')) {
            return 1;
        }
        return 0;
    }
}
