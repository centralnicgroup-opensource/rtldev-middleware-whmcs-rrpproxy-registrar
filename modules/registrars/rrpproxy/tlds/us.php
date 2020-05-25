<?php
switch ($params["additionalfields"]["Application Purpose"]) {
    case "Business use for profit":
        $purpose = "P1";
        break;
    case "Non-profit business":
        $purpose = "P2";
        break;
    case "Club":
        $purpose = "P2";
        break;
    case "Association":
        $purpose = "P2";
        break;
    case "Religious Organization":
        $purpose = "P2";
        break;
    case "Personal Use":
        $purpose = "P3";
        break;
    case "Educational purposes":
        $purpose = "P4";
        break;
    case "Government purposes":
        $purpose = "P5";
        break;
}
$extensions['X-US-NEXUS-APPPURPOSE'] = $purpose;

switch ($params["additionalfields"]["Nexus Category"]) {
    case "C11":
    case "C12":
    case "C21":
        $extensions['X-US-NEXUS-CATEGORY'] = $params["additionalfields"]["Nexus Category"];
        break;
    case "C31":
    case "C32":
        $extensions['X-US-NEXUS-CATEGORY'] = $params["additionalfields"]["Nexus Category"];
        $extensions['X-US-NEXUS-VALIDATOR'] = $params["additionalfields"]["Nexus Country"];
        break;
}
