<?php

$extensions['X-CN-ACCEPT-TRUSTEE-TAC'] = 0;

switch ($params["additionalfields"]['Owner Type']) {
    case 'Enterprise':
        $ownerType = 'E';
        break;
    case 'Individual':
    default:
        $ownerType = 'I';
}

switch ($params['additionalfields']['ID Type']) {
    case 'Beijing School for Children of Foreign Embassy Staff in China Permit':
        $idType = 'BJWSXX';
        break;
    case 'Business License':
        $idType = 'YYZZ';
        break;
    case 'Certificate for Uniform Social Credit Code':
        $idType = 'TYDM';
        break;
    case 'Exit-Entry Permit for Travelling to and from Hong Kong and Macao':
        $idType = 'GAJMTX';
        break;
    case 'Foreign Permanent Resident ID Card':
        $idType = 'WJLSFZ';
        break;
    case 'Fund Legal Person Registration Certificate':
        $idType = 'JJHFR';
        break;
    case 'ID':
        $idType = 'SFZ';
        break;
    case 'Judicial Expertise License':
        $idType = 'SFJD';
        break;
    case 'Medical Institution Practicing License':
        $idType = 'YLJGZY';
        break;
    case 'Military Code Designation':
        $idType = 'BDDM';
        break;
    case 'Military Paid External Service License':
        $idType = 'JDDWFW';
        break;
    case 'Notary Organization Practicing License':
        $idType = 'GZJGZY';
        break;
    case 'Officer’s identity card':
        $idType = 'JGZ';
        break;
    case 'Organization Code Certificate':
        $idType = 'ORG';
        break;
    case 'Others':
        $idType = 'QT';
        break;
    case 'Others-Certificate for Uniform Social Credit Code':
        $idType = 'QTTYDM';
        break;
    case 'Overseas Organization Certificate':
        $idType = 'JWJG';
        break;
    case 'Practicing License of Law Firm':
        $idType = 'LSZY';
        break;
    case 'Private Non-Enterprise Entity Registration Certificate':
        $idType = 'MBFQY';
        break;
    case 'Private School Permit':
        $idType = 'MBXXBX';
        break;
    case 'Public Institution Legal Person Certificate':
        $idType = 'SYDWFR';
        break;
    case 'Registration Certificate of Foreign Cultural Center in China':
        $idType = 'WGZHWH';
        break;
    case 'Religion Activity Site Registration Certificate':
        $idType = 'ZJCS';
        break;
    case 'Residence permit for Hong Kong and Macao residents':
        $idType = 'GAJZZ';
        break;
    case 'Residence permit for Taiwan residents':
        $idType = 'TWJZZ';
        break;
    case 'Resident Representative Office of Tourism Departments of Foreign Government Approval Registration Certificate':
        $idType = 'WLCZJG';
        break;
    case 'Resident Representative Offices of Foreign Enterprises Registration Form':
        $idType = 'WGCZJG';
        break;
    case 'Social Organization Legal Person Registration Certificate':
        $idType = 'SHTTFR';
        break;
    case 'Social Service Agency Registration Certificate':
        $idType = 'SHFWJG';
        break;
    case 'Travel passes for Taiwan Residents to Enter or Leave the Mainland':
        $idType = 'TWJMTX';
        break;
    case 'Passport':
    default:
        $idType = 'HZ';
}

$extensions['X-CN-OWNER-TYPE'] = $ownerType;
$extensions['X-CN-OWNER-ID-NUMBER'] = $params['additionalfields']['ID Number'];
$extensions['X-CN-OWNER-ID-TYPE'] = $idType;
