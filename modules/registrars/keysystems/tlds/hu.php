<?php

if ($params["additionalfields"]["Accept Trustee Service"] || $params["countrycode"] != "HU") {
    $extensions["X-HU-ACCEPT-TRUSTEE-TAC"] = 1;
}
if ($params["additionalfields"]["ID Card or Passport Number"]) {
    $extensions["X-HU-IDCARD-OR-PASSPORT-NUMBER"] = $params["additionalfields"]["ID Card or Passport Number"];
}
if ($params["additionalfields"]["VAT Number"]) {
    $extensions["X-HU-VAT-NUMBER"] = $params["additionalfields"]["VAT Number"];
}
