<?php

//.br
$additionaldomainfields['.com.br'][] = [
    'Name' => 'X-BR-REGISTER-NUMBER',
    'DisplayName' => 'Tax Identification Number (CPF or CNPJ) <sup style="cursor:help;" title="The CPF is the financial identity number provided by the Brazilian Government for every Brazilian citizen in order to charge taxes and financial matters. The CNPJ is the same as the CPF but it works for companies.">what\'s this?</sup>',
    'Type' => 'text',
    'Size' => '20',
    'Required' => true
];
$additionaldomainfields['.abc.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.belem.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.blog.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.emp.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.esp.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.far.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.floripa.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.ind.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.jampa.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.macapa.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.net.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.org.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.poa.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.recife.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.rio.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.sjc.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.tur.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.tv.br'] = $additionaldomainfields['.com.br'];
$additionaldomainfields['.vix.br'] = $additionaldomainfields['.com.br'];

//.ro
$additionaldomainfields['.ro'][] = [
    'Name' => 'CNPFiscalCode',
    'Required' => true
];
$additionaldomainfields['.arts.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.co.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.com.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.firm.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.info.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.nom.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.nt.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.org.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.rec.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.ro.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.store.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.tm.ro'] = $additionaldomainfields['.ro'];
$additionaldomainfields['.www.ro'] = $additionaldomainfields['.ro'];

//.hu
$additionaldomainfields[".hu"][] = [
    "Name" => "Accept Trustee Service",
    "LangVar" => "hutldtac",
    "Type" => "tickbox",
    "Description" => "Required if owner not in the EU"
];
$additionaldomainfields[".hu"][] = [
    "Name" => "ID Card or Passport Number",
    "LangVar" => "hutldpassport",
    "Type" => "text",
    "Default" => "",
    "Description" => "Required for organisations and natural persons"
];
$additionaldomainfields[".hu"][] = [
    "Name" => "VAT Number",
    "LangVar" => "hutldtaxid",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Description" => "Required for organisations"
];

//.pt
$additionaldomainfields[".pt"][] = [
    "Name" => "Owner Identification",
    "LangVar" => "pttldownerid",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Description" => "Fiscal ID (VAT number) of the person or company that you are trying to register without the country code"
];
$additionaldomainfields[".pt"][] = [
    "Name" => "Tech Identification",
    "LangVar" => "pttldtechid",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Description" => "Fiscal ID (VAT number) of the person or company that you are inserting as tech-contact without the country code"
];

//.cn
$additionaldomainfields[".cn"][] = [
    "Name" => "Owner Type",
    "LangVar" => "cntldownertype",
    "Type" => "dropdown",
    "Options" => "Enterprise,Individual",
    "Default" => "Individual",
    "Description" => "Legal type of registrant"
];
$additionaldomainfields[".cn"][] = [
    "Name" => "ID Number",
    "LangVar" => "cntldidnum",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => true
];
$additionaldomainfields[".cn"][] = [
    "Name" => "ID Type",
    "LangVar" => "cntldidtype",
    "Type" => "dropdown",
    "Options" => "Beijing School for Children of Foreign Embassy Staff in China Permit,Business License,Certificate for Uniform Social Credit Code,Exit-Entry Permit for Travelling to and from Hong Kong and Macao,Foreign Permanent Resident ID Card,Fund Legal Person Registration Certificate,ID,Judicial Expertise License,Medical Institution Practicing License,Military Code Designation,Military Paid External Service License,Notary Organization Practicing License,Officer’s identity card,Organization Code Certificate,Others,Others-Certificate for Uniform Social Credit Code,Overseas Organization Certificate,Passport,Practicing License of Law Firm,Private Non-Enterprise Entity Registration Certificate,Private School Permit,Public Institution Legal Person Certificate,Registration Certificate of Foreign Cultural Center in China,Religion Activity Site Registration Certificate,Residence permit for Hong Kong and Macao residents,Residence permit for Taiwan residents,Resident Representative Office of Tourism Departments of Foreign Government Approval Registration Certificate,Resident Representative Offices of Foreign Enterprises Registration Form,Social Organization Legal Person Registration Certificate,Social Service Agency Registration Certificate,Travel passes for Taiwan Residents to Enter or Leave the Mainland",
    "Default" => "Passport"
];
