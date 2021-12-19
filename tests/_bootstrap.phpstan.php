<?php

// This is the bootstrap for PHPStan.

$whmcsPath = realpath(__DIR__ . '/../../whmcs');
$configFile = __DIR__ . '/phpstan.config.php';
if (file_exists($configFile)) {
    include($configFile);
}

define('WHMCS', true);

require_once $whmcsPath . '/vendor/autoload.php';
require_once $whmcsPath . '/includes/functions.php';
//require_once $whmcsPath . '/includes/clientfunctions.php';
//require_once $whmcsPath . '/includes/invoicefunctions.php';
//require_once $whmcsPath . '/includes/quotefunctions.php';
