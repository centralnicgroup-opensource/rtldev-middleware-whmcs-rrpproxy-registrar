<?php
$extensions['X-NU-IIS-IDNO'] = $params["additionalfields"]["Identification Number"];
if (!empty($params["additionalfields"]["VAT Number"])) {
    $extensions['X-NU-IIS-VATNO'] = $params["additionalfields"]["VAT Number"];
}