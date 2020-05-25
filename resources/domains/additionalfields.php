<?php
$rrp_additionalfields = implode(DIRECTORY_SEPARATOR, array(ROOTDIR, "modules", "registrars", "rrpproxy", "lib", "additionalfields.php"));
if (file_exists($rrp_additionalfields)) {
    include $rrp_additionalfields;
}