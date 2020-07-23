<?php
$params["additionalfields"]["ID Form Type"] = $params["additionalfields"]["ID Form Type"];
$idType = $params["additionalfields"]["ID Form Type"][0];
switch ($idType) {
    case "DNI":
    case "NIF":
    case "Tax Identification Number":
    case "Tax Identification Code":
        $idType = 3;
        break;
    case "NIE":
    case "Foreigner Identification Number":
        $idType = 1;
        break;
    default:
        $idType = 0;
}
if (!empty($params["additionalfields"]["Legal Form"])) {
    $params["additionalfields"]["Legal Form"] = $params["additionalfields"]["Legal Form"];
    $legalForm = $params["additionalfields"]["Legal Form"][0];
    if (!is_int($legalForm)) {
        switch ($legalForm) {
            case "Economic Interest Group":
                $legalForm = 39;
                break;
            case "Association":
                $legalForm = 47;
                break;
            case "Sports Association":
                $legalForm = 59;
                break;
            case "Professional Association":
                $legalForm = 68;
                break;
            case "Savings Bank":
                $legalForm = 124;
                break;
            case "Community Property":
                $legalForm = 150;
                break;
            case "Community of Owners":
                $legalForm = 152;
                break;
            case "Order or Religious Institution":
                $legalForm = 164;
                break;
            case "Consulate":
                $legalForm = 181;
                break;
            case "Public Law Association":
                $legalForm = 197;
                break;
            case "Embassy":
                $legalForm = 203;
                break;
            case "Local Authority":
                $legalForm = 229;
                break;
            case "Sports Federation":
                $legalForm = 269;
                break;
            case "Foundation":
                $legalForm = 286;
                break;
            case "Mutual Insurance Company":
                $legalForm = 365;
                break;
            case "Regional Government Body":
                $legalForm = 434;
                break;
            case "Central Government Body":
                $legalForm = 436;
                break;
            case "Political Party":
                $legalForm = 439;
                break;
            case "Trade Union":
                $legalForm = 476;
                break;
            case "Farm Partnership":
                $legalForm = 510;
                break;
            case "Public Limited Company":
                $legalForm = 524;
                break;
            case "Civil Society":
                $legalForm = 554;
                break;
            case "General Partnership":
                $legalForm = 560;
                break;
            case "General and Limited Partnership":
                $legalForm = 562;
                break;
            case "Cooperative":
                $legalForm = 566;
                break;
            case "Worker-owned Company":
                $legalForm = 608;
                break;
            case "Limited Company":
                $legalForm = 612;
                break;
            case "Spanish Office":
                $legalForm = 713;
                break;
            case "Temporary Alliance of Enterprises":
                $legalForm = 717;
                break;
            case "Worker-owned Limited Company":
                $legalForm = 744;
                break;
            case "Regional Public Entity":
                $legalForm = 745;
                break;
            case "National Public Entity":
                $legalForm = 746;
                break;
            case "Local Public Entity":
                $legalForm = 747;
                break;
            case "Others":
                $legalForm = 877;
                break;
            case "Designation of Origin Supervisory Council":
                $legalForm = 878;
                break;
            case "Entity Managing Natural Areas":
                $legalForm = 879;
                break;
            default:
                $legalForm = 1;
        }
    }
} else {
    $legalForm = 1;
}

$extensions['X-ES-OWNER-LEGALFORM'] = $legalForm;
$extensions['X-ES-OWNER-TIPO-IDENTIFICACION'] = $idType;
$extensions['X-ES-OWNER-IDENTIFICACION'] = $params["additionalfields"]["ID Form Number"];
