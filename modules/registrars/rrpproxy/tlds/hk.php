<?php
$extensions['X-ACCEPT-NSCHANGE'] = 0;
if ($params["additionalfields"]['Registrant Type'] == 'ind') {
    $extensions['X-HK-DOMAIN-CATEGORY'] = 'I';
    $extensions['X-HK-OWNER-AGE-OVER-18'] = $params["additionalfields"]['Individuals Under 18'] == 'No' ? 'Yes' : 'No';
    $extensions['X-HK-OWNER-DOCUMENT-NUMBER'] = $params["additionalfields"]['Individuals Document Number'];
    $extensions['X-HK-OWNER-DOCUMENT-ORIGIN-COUNTRY'] = $params["additionalfields"]['Individuals Issuing Country'];
    $extensions['X-HK-OWNER-DOCUMENT-TYPE'] = $params["additionalfields"]['Individuals Supporting Documentation'];
} else {
    $extensions['X-HK-DOMAIN-CATEGORY'] = 'O';
    $extensions['X-HK-OWNER-DOCUMENT-NUMBER'] = $params["additionalfields"]['Organizations Document Number'];
    $extensions['X-HK-OWNER-DOCUMENT-ORIGIN-COUNTRY'] = $params["additionalfields"]['Organizations Issuing Country'];
    $extensions['X-HK-OWNER-DOCUMENT-TYPE'] = $params["additionalfields"]['Organizations Supporting Documentation'];
}
