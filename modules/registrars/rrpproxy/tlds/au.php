<?php
$extensions['X-AU-DOMAIN-IDNUMBER'] = $params["additionalfields"]['Registrant ID'];
$extensions['X-AU-DOMAIN-RELATIONTYPE'] = $params["additionalfields"]['Eligibility Type'];
$extensions['X-AU-OWNER-ORGANIZATION'] = $params["additionalfields"]['Registrant Name'];
$eligibilityType = null;
switch ($params["additionalfields"]['Eligibility ID Type']) {
    case 'Australian Company Number (ACN)':
        $eligibilityType = 'ACN';
        break;
    case 'Australian Business Number (ABN)':
        $eligibilityType = 'ABN';
        break;
    case 'Trademark (TM)':
        $eligibilityType = 'TM';
        break;
    case 'ACT Business Number':
    case 'NSW Business Number':
    case 'NT Business Number':
    case 'QLD Business Number':
    case 'SA Business Number':
    case 'TAS Business Number':
    case 'VIC Business Number':
    case 'WA Business Number':
    case 'Other - Used to record an Incorporated Association number':
        $eligibilityType = 'OTHER';
        break;
}
if ($eligibilityType) {
    $extensions['X-AU-DOMAIN-IDTYPE'] = $eligibilityType;
    $extensions['X-AU-DOMAIN-RELATION'] = $params["additionalfields"]['Eligibility Reason'][0] == 'D' ? 1 : 2;
}
