<?php

$rrp_additionalfields = implode(DIRECTORY_SEPARATOR, array(ROOTDIR, "modules", "registrars", "keysystems", "lib", "additionalfields.php"));
if (file_exists($rrp_additionalfields)) {
    include $rrp_additionalfields;
}
