<?php

// Runs before the WHMCS daily cron
add_hook('PreCronJob', 1, function () {
    $registrar = new \WHMCS\Module\Registrar();
    if (!$registrar->load("keysystems")) {
        localAPI('LogActivity', ['description' => "[keysystems] Daily Cron: unable to load registrar configuration"]);
        return;
    }
    $params = $registrar->getSettings();
    if (!$params["DailyCron"]) {
        localAPI('LogActivity', ['description' => "[keysystems] Daily Cron: disabled"]);
        return;
    }

    require_once __DIR__ . '/vendor/autoload.php';

    localAPI('LogActivity', ['description' => "[keysystems] Daily Cron: executing"]);
    $sync = new \WHMCS\Module\Registrar\RRPproxy\Helpers\Sync($params);
    $sync->sync();
    $reportSent = $sync->sendReport();
    localAPI('LogActivity', ['description' => "[keysystems] Daily Cron: done - " . ($reportSent ? "report sent" : "no report necessary")]);
});
