<?php
$extensions['X-SG-ACCEPT-TRUSTEE-TAC'] = 0;

switch ($params["additionalfields"]["Registrant Type"]) {
    case "Individual":
        $extensions['X-SG-ADMIN-SINGPASSID'] = $params["additionalfields"]["RCB Singapore ID"];
        break;
    case "Organisation":
        $extensions['X-SG-RCBID'] = $params["additionalfields"]["RCB Singapore ID"];
        break;
}
