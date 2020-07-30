<?php

$extensions["X-TEL-PUBLISH-WHOIS"] = $params["additionalfields"]["WHOIS Opt-out"] ? 1 : 0;
$extensions["X-TEL-WHOISTYPE"] = $params["additionalfields"]["Legal Type"] == 'Legal Person' ? 'Legal' : 'Natural';
