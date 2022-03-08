<?php

if ($params["additionalfields"]["Owner Identification"]) {
    $extensions["X-PT-OWNER-IDENTIFICATION"] = $params["additionalfields"]["Owner Identification"];
}
if ($params["additionalfields"]["Tech Identification"]) {
    $extensions["X-PT-TECH-IDENTIFICATION"] = $params["additionalfields"]["Tech Identification"];
}
