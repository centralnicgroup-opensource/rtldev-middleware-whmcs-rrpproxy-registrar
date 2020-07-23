<?php
$extensions['X-FR-ACCEPT-TRUSTEE-TAC'] = 0;

switch ($params["additionalfields"]["Legal Type"]) {
    case "Individual":
        $extensions['X-FR-BIRTHCITY'] = $params["additionalfields"]["Birthplace City"];
        $extensions['X-FR-BIRTHDATE'] = $params["additionalfields"]["Birthdate"];
        $extensions['X-FR-BIRTHPC'] = $params["additionalfields"]["Birthplace Postcode"];
        $extensions['X-FR-BIRTHPLACE'] = $params["additionalfields"]["Birthplace Country"];
        $extensions['X-FR-RESTRICT-PUB'] = 1;
        break;
    case "Company":
        $extensions['X-FR-DUNS'] = $params["additionalfields"]["DUNS Number"];
        $extensions['X-FR-SIREN-OR-SIRET'] = $params["additionalfields"]["SIRET Number"];
        $extensions['X-FR-TRADEMARK'] = $params["additionalfields"]["Trademark Number"];
        $extensions['X-FR-VATID'] = $params["additionalfields"]["VAT Number"];
        break;
}
