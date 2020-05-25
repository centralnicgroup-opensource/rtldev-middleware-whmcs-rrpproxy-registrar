<?php
switch ($params["additionalfields"]["Legal Type"]) {
    case "UK Limited Company":
        $legaltype = "LTD";
        break;
    case "UK Public Limited Company":
        $legaltype = "PLC";
        break;
    case "UK Partnership":
        $legaltype = "PTNR";
        break;
    case "UK Limited Liability Partnership":
        $legaltype = "LLP";
        break;
    case "Sole Trader":
        $legaltype = "STRA";
        break;
    case "UK Registered Charity":
        $legaltype = "RCHAR";
        break;
    case "UK Industrial/Provident Registered Company":
        $legaltype = "IP";
        break;
    case "UK School":
        $legaltype = "SCH";
        break;
    case "UK Government Body":
        $legaltype = "GOV";
        break;
    case "UK Corporation by Royal Charter":
        $legaltype = "CRC";
        break;
    case "UK Statutory Body":
        $legaltype = "STAT";
        break;
    case "Non-UK Individual":
        $legaltype = "FIND";
        break;
    case "Foreign Organization":
        $legaltype = "FCORP";
        break;
    case "Other foreign organizations":
        $legaltype = "FOTHER";
        break;
    default:
        $legaltype = "IND";
        break;
}
$tlds = ['uk', 'co.uk', 'me.uk', 'org.uk'];

if (in_array($params['tld'], $tlds)) {
    $extensions['X-UK-OWNER-CORPORATE-TYPE'] = $legaltype;
    $extensions['X-UK-OWNER-CORPORATE-NUMBER'] = $params["additionalfields"]["Company ID Number"];
}
