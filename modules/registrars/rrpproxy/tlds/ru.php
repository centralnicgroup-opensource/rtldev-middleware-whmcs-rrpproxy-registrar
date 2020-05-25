<?php
switch ($params["additionalfields"]["Registrant Type"]) {
    case "IND|Individual":
        $extensions['X-RU-BIRTHDATE'] = $params["additionalfields"]["Individuals Birthday"];
        $extensions['X-RU-PASSPORTDATA'] = $params["additionalfields"]["Individuals Passport Number"].' - '.$params["additionalfields"]["Individuals Passport Issuer"];
        break;
    case "ORG|Organization":
        $extensions['X-RU-CODE'] = $params["additionalfields"]["Russian Organizations Taxpayer Number 1"];
        $extensions['X-RU-KPP'] = $params["additionalfields"]["Russian Organizations Territory-Linked Taxpayer Number 2"];
        break;
}
