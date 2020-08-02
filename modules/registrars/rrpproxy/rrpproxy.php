<?php

/**
 * WHMCS RRPProxy Registrar Module
 *
 * @author Zoltan Egresi <egresi@globehosting.com>
 * Copyright 2019 Zoltan Egresi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\RRPProxy\RRPProxyClient;

define(RRPPROXY_VERSION, "0.1.0");

require_once __DIR__ . '/lib/RRPProxyClient.php';

function rrpproxy_MetaData()
{
    return array(
        'DisplayName' => 'RRPProxy',
        'APIVersion' => '1.0',
    );
}

function rrpproxy_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'RRPProxy',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => "Don't have a RRPproxy Account yet?" . " Get one here: <a target=\"_blank\" href=\"https://www.rrpproxy.net/Register\">www.rrpproxy.net/Register</a>",
        ],
        'Username' => [
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1024',
            'Description' => 'Enter your RRPProxy Username',
        ],
        'Password' => [
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your RRPProxy Password',
        ],
        'DNSSEC' => [
            'FriendlyName' => 'Allow DNSSEC',
            'Type' => 'yesno',
            'Description' => 'Enables DNSSEC configuration in the client area'
        ],
        'TestMode' => [
            'Type' => 'yesno',
            'Description' => 'Tick to enable OT&amp;E',
        ],
        'TestPassword' => [
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your RRPProxy OT&amp;E Password',
        ]
    ];
}

function rrpproxy_GetDomainInformation(array $params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['domainname']]);

        $nameservers = [];
        $i = 1;
        foreach ($result['property']['nameserver'] as $nameserver) {
            $nameservers["ns" . $i] = $nameserver;
            $i++;
        }

        $domain = new Domain();
        $domain->setIsIrtpEnabled(true);
        $domain->setDomain($result['property']['domain'][0]);
        $domain->setNameservers($nameservers);
        $domain->setTransferLock($result['property']['transferlock']['0']);
        $domain->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s.u', $result['property']['registrationexpirationdate']['0']));

        try {
            //check contact status
            $contact = $api->call('StatusContact', ['contact' => $result["property"]["ownercontact"][0]]);

            $domain->setRegistrantEmailAddress($contact['property']['email'][0]);
            if ($contact['property']['verificationrequested'][0] == 1 && $contact['property']['verified'][0] == 0) {
                $domain->setDomainContactChangePending(true);
            }
        } catch (Exception $ex) {
            return array(
                'error' => $ex->getMessage(),
            );
        }

        if (isset($result['property']['x-time-to-suspension'][0])) {
            $domain->setPendingSuspension(true);
            $domain->setDomainContactChangeExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $result['property']['x-time-to-suspension'][0]));
        }
        $domain->setIrtpVerificationTriggerFields(
            [
                'Registrant' => [
                    'First Name',
                    'Last Name',
                    'Organization Name',
                    'Email',
                ],
            ]
        );

        return $domain;
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

function rrpproxy_ResendIRTPVerificationEmail(array $params)
{
    $domain = rrpproxy_GetDomainInformation($params);
    try {
        $api = new RRPProxyClient();
        $api->call('ResendNotification', ['type' => 'CONTACTVERIFICATION', 'object' => (string)$domain->getRegistrantEmailAddress()]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Register a domain.
 *
 * Attempt to register a domain with the domain registrar.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain registration order
 * * When a pending domain registration order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_RegisterDomain($params)
{
    $params = injectDomainObjectIfNecessary($params);

    if ($params['tld'] == 'it' && $params['additionalfields']['Legal Type'] == 'Italian and foreign natural persons') {
        $params['companyname'] = '';
    }

    // Owner Contact Information
    $ownerContact = [
        'firstname' => $params["firstname"],
        'lastname' => $params["lastname"],
        'email' => $params["email"],
        'street0' => $params["address1"],
        'street1' => $params["address2"],
        'city' => $params["city"],
        'state' => $params["state"],
        'zip' => $params["postcode"],
        'country' => $params["country"],
        'phone' => $params["fullphonenumber"],
        'new' => 0,
        'preverify' => 1,
        'autodelete' => 1
    ];
    if ($params['companyname']) {
        $ownerContact['organization'] = $params['companyname'];
    }

    // Admin Contact Information
    $adminContact = [
        'firstname' => $params["adminfirstname"],
        'lastname' => $params["adminlastname"],
        'email' => $params["adminemail"],
        'street0' => $params["adminaddress1"],
        'street1' => $params["adminaddress2"],
        'city' => $params["admincity"],
        'state' => $params["adminstate"],
        'zip' => $params["adminpostcode"],
        'country' => $params["admincountry"],
        'phone' => $params["adminfullphonenumber"],
        'new' => 0,
        'preverify' => 1
    ];
    if ($params['admincompanyname']) {
        $adminContact['organization'] = $params['admincompanyname'];
    }

    // Create contacts if not existing and get contact handle
    try {
        $api = new RRPProxyClient();
        $contact = $api->call('AddContact', $ownerContact);
        $contact_id = $contact['property']['contact']['0'];
        $contact = $api->call('AddContact', $adminContact);
        $admin_contact_id = $contact['property']['contact']['0'];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }

    // Loading custom RRPProxy TLD Extensions
    $extensions = [];
    $domainApplication = false;
    $extensions_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "tlds", $params["domainObj"]->getLastTLDSegment() . ".php"));
    if (file_exists($extensions_path)) {
        require_once $extensions_path;
    }

    $fields = [
        'domain' => $params['domainname'],
        'period' => $params['regperiod'],
        'nameserver0' => $params['ns1'],
        'nameserver1' => $params['ns2'],
        'nameserver2' => $params['ns3'],
        'nameserver3' => $params['ns4'],
        'nameserver4' => $params['ns5'],
        'ownercontact0' => $contact_id,
        'admincontact0' => $admin_contact_id,
        'techcontact0' => $admin_contact_id,
        'billingcontact0' => $admin_contact_id,
        'X-WHOISPRIVACY' => $params['idprotection'] ? 1 : 0
    ];
    $request = array_merge($fields, $extensions);

    //Register the domain name
    try {
        $api = new RRPProxyClient();
        $api->call($domainApplication ? 'AddDomainApplication' : 'AddDomain', $request);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Initiate domain transfer.
 *
 * Attempt to create a domain transfer request for a given domain.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain transfer order
 * * When a pending domain transfer order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_TransferDomain($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('TransferDomain', ['domain' => $params['domainname'], 'auth' => $params['eppcode']]);
        return array(
            'success' => true,
        );
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Renew a domain.
 *
 * Attempt to renew/extend a domain for a given number of years.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain renewal order
 * * When a pending domain renewal order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_RenewDomain($params)
{
    $domain = WHMCS\Domain\Domain::find($params['domainid']);

    try {
        $renewOnceTlds = ['at', 'be', 'ch', 'co.za', 'de', 'dk', 'fr', 'hu', 'is', 'it', 'jp', 'li', 'lu', 'my', 'nl', 'pm', 're', 'ru', 'sk', 'su', 'ua'];
        $api = new RRPProxyClient();

        if (in_array($params["domainObj"]->getLastTLDSegment(), $renewOnceTlds)) {
            $api->call('SetDomainRenewalMode', ['domain' => $params['domainname'], 'renewalmode' => 'RENEWONCETHENAUTODELETE']);
        } else {
            $api->call('RenewDomain', ['domain' => $params['domainname'], 'period' => $params["regperiod"], 'expiration' => $domain->expirydate->year]);
        }

        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Fetch current nameservers.
 *
 * This function should return an array of nameservers for a given domain.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_GetNameservers($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['domainname']]);
        return array(
            'ns1' => $result['property']['nameserver'][0],
            'ns2' => $result['property']['nameserver'][1],
            'ns3' => $result['property']['nameserver'][2],
            'ns4' => $result['property']['nameserver'][3],
            'ns5' => $result['property']['nameserver'][4],
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Save nameserver changes.
 *
 * This function should submit a change of nameservers request to the
 * domain registrar.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_SaveNameservers($params)
{
    $fields = array(
        'domain' => $params['sld'] . '.' . $params['tld'],
        'nameserver0' => $params['ns1'],
        'nameserver1' => $params['ns2'],
        'nameserver2' => $params['ns3'],
        'nameserver3' => $params['ns4'],
        'nameserver4' => $params['ns5'],
    );

    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', $fields);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Get the current WHOIS Contact Information.
 *
 * Should return a multi-level array of the contacts and name/address
 * fields that be modified.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_GetContactDetails($params)
{
    try {
        $api = new RRPProxyClient();
        $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);
        $contacts['Registrant'] = $api->getContactInfo($response["property"]["ownercontact"][0]);

        return $contacts;
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * Called when a change of WHOIS Information is requested within WHMCS.
 * Receives an array matching the format provided via the `GetContactDetails`
 * method with the values from the users input.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_SaveContactDetails($params)
{
    $contactDetails = $params['contactdetails'];

    try {
        $api = new RRPProxyClient();
        $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);

        $owner = [
            'contact' => $response["property"]["ownercontact"][0],
            'validation' => true,
            'firstname' => $contactDetails['Registrant']['First Name'],
            'lastname' => $contactDetails['Registrant']['Last Name'],
            'organization' => $contactDetails['Registrant']['Company Name'],
            'street0' => $contactDetails['Registrant']['Address'],
            'street1' => $contactDetails['Registrant']['Address 2'],
            'city' => $contactDetails['Registrant']['City'],
            'state' => $contactDetails['Registrant']['State'],
            'zip' => $contactDetails['Registrant']['Postcode'],
            'country' => $contactDetails['Registrant']['Country'],
            'phone' => $contactDetails['Registrant']['Phone'],
            'fax' => $contactDetails['Registrant']['Fax'],
            'email' => $contactDetails['Registrant']['Email'],
        ];

        try {
            $api->call('ModifyContact', $owner);
        } catch (Exception $ex) {
            return array(
                'error' => $ex->getMessage(),
            );
        }
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @return ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 * @throws Exception Upon domain availability check failure.
 *
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 */
function rrpproxy_CheckAvailability($params)
{

    /* need to implement PREMIUM DOMAINS */

    try {
        $api = new RRPProxyClient();

        if ($params['isIdnDomain']) {
            $searchTerm = empty($params['punyCodeSearchTerm']) ? strtolower($params['searchTerm']) : strtolower($params['punyCodeSearchTerm']);
        } else {
            $searchTerm = strtolower($params['searchTerm']);
        }

        $tldsToInclude = $params['tldsToInclude'];
        $results = new ResultsList();

        foreach ($tldsToInclude as $tld) {
            $result = $api->call('CheckDomain', ['domain' => $searchTerm . $tld]);
            $searchResult = new SearchResult($searchTerm, $tld);
            if ($result['code'] == 210) {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } elseif ($result['code'] == 211) {
                $status = SearchResult::STATUS_REGISTERED;
            } else {
                $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);
            $results->append($searchResult);
        }
        return $results;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get Domain Suggestions.
 *
 * Provide domain suggestions based on the domain lookup term provided.
 *
 * @param array $params common module parameters
 * @return ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 * @throws Exception Upon domain suggestions check failure.
 *
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 */
function rrpproxy_GetDomainSuggestions($params)
{
    /* need to implement PREMIUM DOMAINS */
    try {
        $api = new RRPProxyClient();
        $response = $api->call('GetNameSuggestion', ['name' => $params['searchTerm'], 'show-unavailable' => 0]);
        $results = new ResultsList();
        foreach ($response['property']['name'] as $key => $domain) {
            $d = explode('.', $domain, 2);
            if ($response['property']['availability'][$key] == 'available') {//include only available domains
                $searchResult = new SearchResult($d[0], $d[1]);
                $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
                $results->append($searchResult);
            }
        }
        return $results;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get registrar lock status.
 *
 * Also known as Domain Lock or Transfer Lock status.
 *
 * @param array $params common module parameters
 *
 * @return string|array Lock status or error message
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_GetRegistrarLock($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['domainname']]);
        if ($result['property']['transferlock'][0] == 0) {
            return "unlocked";
        } else {
            return "locked";
        }
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Set registrar lock status.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_SaveRegistrarLock($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', ['domain' => $params['domainname'], 'transferlock' => ($params['lockenabled'] == 'locked') ? 1 : 0]);
        return array(
            'success' => 'success',
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array $params common module parameters
 *
 * @return array DNS Host Records
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_GetDNS($params)
{
    /* Need to add a check if the DNZ Zone Exists */

    try {
        $api = new RRPProxyClient();
        $response = $api->call('QueryDNSZoneRRList', ['dnszone' => $params['domainname'], 'wide' => 1]);

        foreach ($response['property']['type'] as $key => $type) {
            $content = str_replace($response['property']['prio'][$key] . ' ', '', $response['property']['content'][$key]);
            $records[$key] = ['hostname' => $response['property']['name'][$key], 'type' => $type, 'address' => $content, 'priority' => $response['property']['prio'][$key], 'recid' => $key];
        }
        return $records;
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Update DNS Host Records.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_SaveDNS($params)
{
    $fields = array(
        'dnszone' => $params['domainname']
    );

    $api = new RRPProxyClient();

    try {
        $response = $api->call('QueryDNSZoneRRList', ['dnszone' => $params['domainname'], 'wide' => 1]);

        foreach ($response['property']['type'] as $key => $type) {
            $content = str_replace($response['property']['prio'][$key] . ' ', '', $response['property']['content'][$key]);
            $records[$key] = ['hostname' => $response['property']['name'][$key], 'type' => $type, 'address' => $content, 'priority' => $response['property']['prio'][$key], 'recid' => $key];
        }
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
    //Delete All Records
    foreach ($records as $key => $value) {
        if ($value == 'MX') {
            $priority = ' ' . $value['priority'] . ' ';
        } else {
            $priority = ' ';
        }
        $fields['DELRR' . $key] = $value['hostname'] . " " . $value['type'] . $priority . $value['address'];
    }

    //Add Records
    foreach ($params['dnsrecords'] as $key => $value) {
        if ($value == 'MX') {
            $priority = ' ' . $value['priority'] . ' ';
        } else {
            $priority = ' ';
        }
        if (isset($value['hostname']) && $value['hostname'] != '' && isset($value['type']) && $value['type'] != '' && isset($value['address']) && $value['address'] != '') {
            $fields['ADDRR' . $key] = $value['hostname'] . " " . $value['type'] . $priority . $value['address'];
        }
    }

    try {
        $api->call('ModifyDNSZone', $fields);

        return array(
            'success' => 'success',
        );
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get Email Forwardings
 *
 * @param array $params common module parameters
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function rrpproxy_GetEmailForwarding($params)
{
    $api = new RRPProxyClient();
    try {
        $response = $api->call('QueryMailFwdList', ['dnszone' => $params['domainname']]);
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    $values = [];
    for ($i = 0; $i < $response['property']['total'][0]; $i++) {
        $from = explode("@", $response['property']['from'][$i]);
        $values[$i] = ['prefix' => $from[0], 'forwardto' => $response['property']['to'][$i]];
    }
    return $values;
}

/**
 * Save Email Forwarding
 *
 * @param array $params common module parameters
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function rrpproxy_SaveEmailForwarding($params)
{
    $api = new RRPProxyClient();
    try {
        $response = $api->call('QueryMailFwdList', ['dnszone' => $params['domainname']]);
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    $orig = [];
    $add = [];
    $del = [];

    for ($i = 0; $i < $response['property']['total'][0]; $i++) {
        $orig[$response['property']['from'][$i]] = $response['property']['to'][$i];
    }

    foreach ($params["prefix"] as $key => $value) {
        $from = $value . "@" . $params['domainname'];
        $to = $params["forwardto"][$key];
        if (!$value || !$to) {
            // invalid
            continue;
        }
        if (isset($orig[$from])) {
            // already present
            if ($orig[$from] == $to) {
                // no change needed
                continue;
            } else {
                // differs, needs to be deleted and readded
                $del[$from] = $orig[$from];
                $add[$from] = $to;
            }
        } else {
            // not present, needs to be added
            $add[$from] = $to;
        }
    }

    foreach ($orig as $from => $to) {
        $explode = explode("@", $from);
        if (!in_array($explode[0], $params["prefix"])) {
            // not present locally anymore, needs to be deleted
            $del[$from] = $to;
        }
    }

    $values = [];

    foreach ($del as $from => $to) {
        try {
            $api->call('DeleteMailFwd', ['from' => $from, 'to' => $to]);
        } catch (Exception $e) {
            $values['error'] .= $e->getMessage() . " ";
        }
    }

    foreach ($add as $from => $to) {
        try {
            $api->call('AddMailFwd', ['from' => $from, 'to' => $to]);
        } catch (Exception $e) {
            $values['error'] .= $e->getMessage() . " ";
        }
    }

    return $values;
}

/**
 * Enable/Disable ID Protection.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_IDProtectToggle($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', ['domain' => $params['domainname'], 'X-WHOISPRIVACY' => ($params["protectenable"]) ? "1" : "0"]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Request EEP Code.
 *
 * Supports both displaying the EPP Code directly to a user or indicating
 * that the EPP Code will be emailed to the registrant.
 *
 * @param array $params common module parameters
 *
 * @return array
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_GetEPPCode($params)
{
    try {
        $api = new RRPProxyClient();
        $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);

        if (strlen($response["property"]["auth"][0])) {
            return array(
                'eppcode' => htmlspecialchars($response["property"]["auth"][0])
            );
        } else {
            return array(
                'error' => "No Auth Info code found!"
            );
        }
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Release a Domain.
 *
 * This feature currently works for .DE, .UK domains and .AT domains.
 *
 * TARGET    Where to push the domain
 * .DE target: TRANSIT (push domain back to registry)
 * .AT target: REGISTRY (push domain back to registry)
 * .UK target: EXAMPLE-TAG-HOLDER (new IPS TAG) DETAGGED (push domain back to registry)
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_ReleaseDomain($params)
{
// Build post data
    $fields = array(
        'domain' => $params['domainname'],
    );
    if (!empty($params['transfertag'])) {
        $fields["target"] = $params['transfertag'];
    }

    try {
        $api = new RRPProxyClient();
        $api->call('PushDomain', $fields);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Delete Domain.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_RequestDelete($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('DeleteDomain', ['domain' => $params['domainname']]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Register a Nameserver.
 *
 * Adds a child nameserver for the given domain name.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_RegisterNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('AddNameserver', ['nameserver' => $params['nameserver'], 'ipaddress0' => $params["ipaddress"]]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Modify a Nameserver.
 *
 * Modifies the IP of a child nameserver.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_ModifyNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyNameserver', ['nameserver' => $params['nameserver'], 'delipaddress0' => $params["currentipaddress"], 'addipaddress0' => $params["newipaddress"]]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Delete a Nameserver.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_DeleteNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('DeleteNameserver', ['nameserver' => $params['nameserver']]);
        return array(
            'success' => true,
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * Domain syncing is intended to ensure domain status and expiry date
 * changes made directly at the domain registrar are synced to WHMCS.
 * It is called periodically for a domain.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_Sync($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['sld'] . '.' . $params['tld']]);
        return array(
            'expirydate' => Carbon::createFromFormat('Y-m-d H:i:s.u', $result['property']['registrationexpirationdate']['0'])->toDateString(), // Format: YYYY-MM-DD
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * Incoming Domain Transfer Sync.
 *
 * Check status of incoming domain transfers and notify end-user upon
 * completion. This function is called daily for incoming domains.
 *
 * @param array $params common module parameters
 *
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_TransferSync($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['sld'] . '.' . $params['tld']]);
        return array(
            'completed' => true,
            'expirydate' => Carbon::createFromFormat('Y-m-d H:i:s.u', $result['property']['registrationexpirationdate']['0'])->toDateString(), // Format: YYYY-MM-DD
        );
    } catch (Exception $ex) {
        return array(
            'error' => $ex->getMessage(),
        );
    }
}

/**
 * DNSSEC Management
 *
 * @param array $params common module parameters
 *
 * @return array an array with a template name
 */
function rrpproxy_dnssec($params)
{
    $api = new RRPProxyClient();
    $error = null;

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["DNSSEC"])) {
            $fields = [];
            $i = 0;
            foreach ($_POST["DNSSEC"] as $key => $record) {
                $record = array_map('trim', $record);
                if (!in_array('', $record)) {
                    $fields["dnssec" . $i++] = implode(" ", $record);
                }
            }
            if (!$fields) {
                $fields['DNSSECDELALL'] = 1;
            }
            $fields['domain'] = $params['domainname'];
            $api->call('ModifyDomain', $fields);
        }

        $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);
        $dsdata_rrp = (isset($response['property']['dnssecdsdata'])) ? $response['property']['dnssecdsdata'] : [];
        $keydata_rrp = (isset($response['property']['dnssec'])) ? $response['property']['dnssec'] : [];

        $dsData = [];
        foreach ($dsdata_rrp as $ds) {
            list($keytag, $alg, $digesttype, $digest) = preg_split('/\s+/', $ds);
            array_push($dsData, ["keytag" => $keytag, "alg" => $alg, "digesttype" => $digesttype, "digest" => $digest]);
        }
        $keyData = [];
        foreach ($keydata_rrp as $key) {
            list($flags, $protocol, $alg, $pubkey) = preg_split('/\s+/', $key);
            array_push($keyData, ["flags" => $flags, "protocol" => $protocol, "alg" => $alg, "pubkey" => $pubkey]);
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }

    return [
        'templatefile' => "dnssec",
        'vars' => [
            'flagOptions' => [
                256 => 'Zone Signing Key',
                257 => 'Key Signing Key'
            ],
            'algOptions' => [
                3 => 'DSA/SHA-1',
                4 => 'Elliptic Curve',
                5 => 'RSA/SHA-1',
                6 => 'DSA-NSEC3-SHA1',
                7 => 'RSASHA1-NSEC3-SHA1',
                8 => 'RSA/SHA256',
                10 => 'RSA/SHA512',
                12 => 'GOST R 34.10-2001',
                13 => 'ECDSA/SHA-256',
                14 => 'ECDSA/SHA-384',
                15 => 'Ed25519',
                16 => 'Ed448'
            ],
            'digestOptions' => [
                1 => 'SHA-1',
                2 => 'SHA-256',
                3 => 'GOST R 34.11-94',
                4 => 'SHA-384'
            ],
            'dsdata' => $dsData,
            'ksdata' => $keyData,
            'successful' => ($_SERVER['REQUEST_METHOD'] === 'POST' && $error == null),
            'error' => $error
        ]
    ];
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 * In this example, we register a Push Domain action which triggers
 * the `rrpproxy_push` function when invoked.
 *
 * @return array
 */
function rrpproxy_ClientAreaCustomButtonArray($params)
{
    return null;
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @return array
 */
function rrpproxy_ClientAreaAllowedFunctions($params)
{
    $functions = [];
    if ($params['DNSSEC']) {
        $functions['DNSSEC Management'] = 'dnssec';
    }
    return $functions;
}

/**
 * Client Area Output.
 *
 * This function renders output to the domain details interface within
 * the client area. The return should be the HTML to be output.
 *
 * @param array $params common module parameters
 *
 * @return string HTML Output
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function rrpproxy_ClientArea($params)
{
    return null;
}
