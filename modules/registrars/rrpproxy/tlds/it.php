<?php
if (!empty($params["additionalfields"]["Publish Personal Data"])) {
    $extensions["X-IT-CONSENTFORPUBLISHING"] = "1";
} else {
    $extensions["X-IT-CONSENTFORPUBLISHING"] = "0";
}

if (!empty($params["additionalfields"]["Accept Section 3 of .IT registrar contract"])) {
    $extensions["X-IT-SECT3-LIABILITY"] = "1";
} else {
    $extensions["X-IT-SECT3-LIABILITY"] = "0";
}

if (!empty($params["additionalfields"]["Accept Section 5 of .IT registrar contract"])) {
    $extensions["X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION"] = "1";
} else {
    $extensions["X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION"] = "0";
}

if (!empty($params["additionalfields"]["Accept Section 6 of .IT registrar contract"])) {
    $extensions["X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION"] = "1";
} else {
    $extensions["X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION"] = "0";
}

if (!empty($params["additionalfields"]["Accept Section 7 of .IT registrar contract"])) {
    $extensions["X-IT-SECT7-EXPLICIT-ACCEPTANCE"] = "1";
} else {
    $extensions["X-IT-SECT7-EXPLICIT-ACCEPTANCE"] = "0";
}

switch ($params["additionalfields"]["Legal Type"]) {
    case "Italian and foreign natural persons":
        $legaltype = "1";
        break;
    case "Companies/one man companies":
        $legaltype = "1";
        break;
    case "Freelance workers/professionals":
        $legaltype = "2";
        break;
    case "Freelance workers/professionals":
        $legaltype = "3";
        break;
    case "non-profit organizations":
        $legaltype = "4";
        break;
    case "public organizations":
        $legaltype = "5";
        break;
        case "other subjects":
        $legaltype = "6";
        break;
    case "non natural foreigners":
        $legaltype = "7";
        break;
}
$extensions["X-IT-PIN"] = $params["additionalfields"]["Tax ID"];
$extensions["X-IT-ENTITY-TYPE"] = $legaltype;
