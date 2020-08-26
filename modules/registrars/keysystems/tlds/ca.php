<?php

$legalType = null;
switch ($params["additionalfields"]["Legal Type"]) {
    case 'Corporation':
        $legalType = "CCO";
        break;
    case 'Canadian Citizen':
        $legalType = "CCT";
        break;
    case 'Permanent Resident of Canada':
        $legalType = "RES";
        break;
    case 'Government':
        $legalType = "GOV";
        break;
    case 'Canadian Educational Institution':
        $legalType = "EDU";
        break;
    case 'Canadian Unincorporated Association':
        $legalType = "ASS";
        break;
    case 'Canadian Hospital':
        $legalType = "HOP";
        break;
    case 'Partnership Registered in Canada':
        $legalType = "PRT";
        break;
    case 'Trade-mark registered in Canada':
        $legalType = "TDM";
        break;
    case 'Canadian Trade Union':
        $legalType = "TRD";
        break;
    case 'Canadian Political Party':
        $legalType = "PLT";
        break;
    case 'Canadian Library Archive or Museum':
        $legalType = "LAM";
        break;
    case 'Trust established in Canada':
        $legalType = "TRS";
        break;
    case 'Aboriginal Peoples':
        $legalType = "ABO";
        break;
    case 'Legal Representative of a Canadian Citizen':
        $legalType = "LGR";
        break;
    case 'Official mark registered in Canada':
        $legalType = "OMK";
        break;
}
if ($legalType) {
    $extensions["X-CA-LEGAL-TYPE"] = $legalType;
    $extensions["X-CA-TRADEMARK:"] = "0";
}
