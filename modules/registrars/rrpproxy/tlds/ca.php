<?php

switch ($params["additionalfields"]["Legal Type"]) {
    case "Corporation":
        $legaltype = "CCO";
        break;
    case "Canadian Citizen":
        $legaltype = "CCT";
        break;
    case "Permanent Resident of Canada":
        $legaltype = "RES";
        break;
    case "Government":
        $legaltype = "GOV";
        break;
    case "Canadian Educational Institution":
        $legaltype = "EDU";
        break;
    case "Canadian Unincorporated Association":
        $legaltype = "ASS";
        break;
    case "Canadian Hospital":
        $legaltype = "HOP";
        break;
    case "Partnership Registered in Canada":
        $legaltype = "PRT";
        break;
    case "Trade-mark registered in Canada":
        $legaltype = "TDM";
        break;
    default:
        $legaltype = "CCO";
        break;
}

$extensions['X-CA-LEGAL-TYPE'] = $legaltype;
$extensions['X-CA-TRADEMARK'] = "0";
