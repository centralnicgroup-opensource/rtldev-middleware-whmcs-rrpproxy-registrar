<?php

$entityType = 0;
switch ($params["additionalfields"]['Legal Type']) {
    case 'Companies/one man companies':
        $entityType = 2;
        break;
    case 'Freelance workers/professionals':
        $entityType = 3;
        break;
    case 'non-profit organizations':
        $entityType = 4;
        break;
    case 'public organizations':
        $entityType = 5;
        break;
    case 'other subjects':
        $entityType = 6;
        break;
    case 'non natural foreigners':
        $entityType = 7;
        break;
    case 'Italian and foreign natural persons':
    default:
        $entityType = 1;
}
$extensions['X-IT-ACCEPT-TRUSTEE-TAC'] = 0;
$extensions['X-IT-CONSENTFORPUBLISHING'] = $params["additionalfields"]['Publish Personal Data'] ? 1 : 0;
$extensions['X-IT-ENTITY-TYPE'] = $entityType;
$extensions['X-IT-PIN'] = $params["additionalfields"]['Tax ID'] ? $params["additionalfields"]['Tax ID'] : 'na';
$extensions['X-IT-SECT3-LIABILITY'] = $params["additionalfields"]['Accept Section 3 of .IT registrar contract'] ? 1 : 0;
$extensions['X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION'] = $params["additionalfields"]['Accept Section 5 of .IT registrar contract'] ? 1 : 0;
$extensions['X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION'] = $params["additionalfields"]['Accept Section 6 of .IT registrar contract'] ? 1 : 0;
$extensions['X-IT-SECT7-EXPLICIT-ACCEPTANCE'] = $params["additionalfields"]['Accept Section 7 of .IT registrar contract'] ? 1 : 0;
