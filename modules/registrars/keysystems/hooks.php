<?php

use WHMCS\Module\Registrar\Keysystems\Widgets\AccountWidget;

// add widget output
add_hook("AdminHomeWidgets", 1, function () {

    require_once __DIR__ . '/keysystems.php';

    return new AccountWidget();
});


// Runs before the WHMCS daily cron
add_hook('PreCronJob', 1, function () {
    $registrar = new \WHMCS\Module\Registrar();
    if (!$registrar->load("keysystems")) {
        localAPI('LogActivity', ['description' => "[CentralNic Reseller] Daily Cron: unable to load registrar configuration"]);
        return;
    }
    $params = $registrar->getSettings();
    if (!$params["DailyCron"]) {
        localAPI('LogActivity', ['description' => "[CentralNic Reseller] Daily Cron: disabled"]);
        return;
    }

    require_once __DIR__ . '/vendor/autoload.php';

    localAPI('LogActivity', ['description' => "[CentralNic Reseller] Daily Cron: executing"]);
    $sync = new \WHMCS\Module\Registrar\Keysystems\Helpers\Sync($params);
    $sync->sync();
    $reportSent = $sync->sendReport();
    localAPI('LogActivity', ['description' => "[CentralNic Reseller] Daily Cron: done - " . ($reportSent ? "report sent" : "no report necessary")]);
});
