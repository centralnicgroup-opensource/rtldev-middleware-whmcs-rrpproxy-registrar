<?php

/**
 * WHMCS RRPproxy Registrar Module
 *
 * @author Sebastian Vassiliou <svassiliou@hexonet.net>
 * Copyright 2020 Key-Systems GmbH
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

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domains\DomainLookup\ResultsList as DomainResults;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;
use WHMCS\Module\Registrar\RRPProxy\RRPProxyClient;

define("RRPPROXY_VERSION", "0.2.1");

require_once __DIR__ . '/lib/RRPProxyClient.php';

function keysystems_getConfigArray()
{
    if (DB::schema()->hasTable('mod_rrpproxy_zones') && !DB::schema()->hasColumn('mod_rrpproxy_zones', 'supports_renewals')) {
        DB::schema()->drop('mod_rrpproxy_zones');
    }

    if (!DB::schema()->hasTable('mod_rrpproxy_zones')) {
        DB::schema()->create('mod_rrpproxy_zones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('zone', 45);
            $table->string('periods', 50);
            $table->integer('grace_days')->nullable();
            $table->integer('redemption_days')->nullable();
            $table->boolean('epp_required');
            $table->boolean('id_protection');
            $table->boolean('supports_renewals');
            $table->boolean('renews_on_transfer');
            $table->boolean('handle_updatable');
            $table->boolean('needs_trade');
            $table->timestamps();
            $table->unique('zone');
        });
        $mod_rrpproxy_zones = [];
        include_once __DIR__ . '/sql/mod_rrpproxy_zones.php';
        DB::table('mod_rrpproxy_zones')->insert($mod_rrpproxy_zones);
        DB::table('mod_rrpproxy_zones')->update(['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    $oldModule = 'rrpproxy';
    $newModule = 'keysystems';
    if (@$_GET['migrate']) {
        DB::table('tbldomains')->where('registrar', $oldModule)->update(['registrar' => $newModule]);
        DB::table('tbldomainpricing')->where('autoreg', $oldModule)->update(['autoreg' => $newModule]);
        DB::table('tblregistrars')->where('registrar', $oldModule)->delete();
    }
    $migrate = '';
    if (
        DB::table('tbldomains')->where('registrar', $oldModule)->count() > 0
        || DB::table('tbldomainpricing')->where('autoreg', $oldModule)->count() > 0
    ) {
        $migrate .= "<br /><a href='configregistrars.php?migrate=true&amp;saved=true#keysystems' class='btn btn-sm btn-default'>Migrate from old RRPproxy module</a>";
    }

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'RRPproxy v' . RRPPROXY_VERSION
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => "Don't have a RRPproxy Account yet? Get one here: <a target=\"_blank\" href=\"https://www.rrpproxy.net/Register\">www.rrpproxy.net/Register</a>" . $migrate,
        ],
        'Username' => [
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1024',
            'Description' => 'Enter your RRPproxy Username',
        ],
        'Password' => [
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your RRPproxy Password',
        ],
        'DefaultTTL' => [
            'FriendlyName' => 'Default TTL',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '28800',
            'Description' => 'Default TTL value in seconds for DNS records'
        ],
        'DNSSEC' => [
            'FriendlyName' => 'Allow DNSSEC',
            'Type' => 'yesno',
            'Description' => 'Enables DNSSEC configuration in the client area'
        ],
        'DeleteMode' => [
            'FriendlyName' => 'Domain deletion mode',
            'Type' => 'dropdown',
            'Options' => ['ImmediateIfPossible', 'AutoDeleteOnExpiry'],
            'Default' => 'ImmediateIfPossible',
        ],
        'TestMode' => [
            'Type' => 'yesno',
            'Description' => 'Tick to enable OT&amp;E',
        ],
        'TestPassword' => [
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your RRPproxy OT&amp;E Password',
        ]
    ];
}

function keysystems_GetDomainInformation(array $params)
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
            return ['error' => $ex->getMessage()];
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
        return ['error' => $ex->getMessage()];
    }
}

function keysystems_ResendIRTPVerificationEmail(array $params)
{
    $domain = keysystems_GetDomainInformation($params);
    try {
        $api = new RRPProxyClient();
        $api->call('ResendNotification', ['type' => 'CONTACTVERIFICATION', 'object' => (string)$domain->getRegistrantEmailAddress()]);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_RegisterDomain($params)
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

    // Loading custom RRPproxy TLD Extensions
    $extensions = [];
    $domainApplication = false;
    $extensions_path = implode(DIRECTORY_SEPARATOR, [__DIR__, "tlds", $params["domainObj"]->getLastTLDSegment() . ".php"]);
    if (file_exists($extensions_path)) {
        require_once $extensions_path;
    }

    $fields = [
        'domain' => $params['domainname'],
        'period' => $params['regperiod'],
        'transferlock' => 1,
        'ownercontact0' => $contact_id,
        'admincontact0' => $admin_contact_id,
        'techcontact0' => $admin_contact_id,
        'billingcontact0' => $admin_contact_id,
        'nameserver0' => $params['ns1'],
        'nameserver1' => $params['ns2']
    ];
    if (!empty($params['ns3'])) {
        $fields['nameserver2'] = $params['ns3'];
    }
    if (!empty($params['ns4'])) {
        $fields['nameserver3'] = $params['ns4'];
    }
    if (!empty($params['ns5'])) {
        $fields['nameserver4'] = $params['ns5'];
    }
    if ($params['idprotection']) {
        $fields['X-WHOISPRIVACY'] = 1;
    }
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
function keysystems_TransferDomain($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('TransferDomain', ['domain' => $params['domainname'], 'auth' => $params['eppcode']]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
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
function keysystems_RenewDomain($params)
{
    $domain = WHMCS\Domain\Domain::find($params['domainid']);
    $api = new RRPProxyClient();

    // This avoids to renew domain after client paid, if we already did so because of autorenew or manually.
    try {
        $response = $api->call('StatusDomain', ['domain' => $domain]);
        $date = $response['property']['registrationexpirationdate'][0];
        $expirationDate = new DateTime(date('Y-m-d', strtotime($date)));
        $currentDate = new DateTime(date('Y-m-d'));
        $diff = $currentDate->diff($expirationDate);
        $years = round($diff->days / 365);
        if ($years > 0) {
            $params["regperiod"] -= $years;
        }
    } catch (Exception $e) {
        return ['error' => 'Exception: ' . $e->getMessage()];
    }

    try {
        $zoneInfo = $api->getZoneInfo($params["domainObj"]->getLastTLDSegment());
        if (!$zoneInfo->supports_renewals) {
            $api->call('SetDomainRenewalMode', ['domain' => $params['domainname'], 'renewalmode' => 'RENEWONCE']);
        } else {
            $api->call('RenewDomain', ['domain' => $params['domainname'], 'period' => $params["regperiod"], 'expiration' => $domain->expirydate->year]);
        }
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_GetNameservers($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['domainname']]);

        $values = [
            'ns1' => $result['property']['nameserver'][0],
            'ns2' => $result['property']['nameserver'][1]
        ];
        if (isset($result['property']['nameserver'][2])) {
            $values['ns3'] = $result['property']['nameserver'][2];
        }
        if (isset($result['property']['nameserver'][3])) {
            $values['ns4'] = $result['property']['nameserver'][3];
        }
        if (isset($result['property']['nameserver'][4])) {
            $values['ns5'] = $result['property']['nameserver'][4];
        }
        return $values;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_SaveNameservers($params)
{
    $fields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'nameserver0' => $params['ns1'],
        'nameserver1' => $params['ns2'],
        'nameserver2' => $params['ns3'],
        'nameserver3' => $params['ns4'],
        'nameserver4' => $params['ns5'],
    ];

    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', $fields);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_GetContactDetails($params)
{
    try {
        $api = new RRPProxyClient();
        $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);

        $owner_id = $response["property"]["ownercontact"][0];
        $admin_id = $response["property"]["admincontact"][0];
        $bill_id = $response["property"]["billingcontact"][0];
        $tech_id = $response["property"]["techcontact"][0];

        $contacts['Registrant'] = $api->getContactInfo($owner_id);

        if (\WHMCS\Config\Setting::getValue('RegistrarAdminUseClientDetails')) {
            if ($admin_id) {
                $contacts['Admin'] = $api->getContactInfo($admin_id);
            }
            if ($bill_id) {
                $contacts['Billing'] = $api->getContactInfo($bill_id);
            }
            if ($tech_id) {
                $contacts['Tech'] = $api->getContactInfo($tech_id);
            }
        }

        return $contacts;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_SaveContactDetails($params)
{
    $domain = $params['sld'] . '.' . $params['tld'];
    $values = [];
    $args = [];

    try {
        $api = new RRPProxyClient();
        $response = $api->call('StatusDomain', ['domain' => $domain]);
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }

    $owner_id = $response['property']['ownercontact'][0];
    $admin_id = $response['property']['admincontact'][0];
    $bill_id = $response['property']['billingcontact'][0];
    $tech_id = $response['property']['techcontact'][0];

    $zoneInfo = $api->getZoneInfo($params['tld']);

    $contact_id = $api->updateContact($zoneInfo->handle_updatable, $owner_id, $params['contactdetails']['Registrant']);
    if ($contact_id != null) {
        $args['ownercontact0'] = $contact_id;
    }
    if (\WHMCS\Config\Setting::getValue('RegistrarAdminUseClientDetails')) {
        if ($admin_id) {
            $contact_id = $api->updateContact($zoneInfo->handle_updatable, $admin_id, $params['contactdetails']['Admin']);
            if ($contact_id != null) {
                $args['admincontact0'] = $contact_id;
            }
        }
        if ($bill_id) {
            $contact_id = $api->updateContact($zoneInfo->handle_updatable, $bill_id, $params['contactdetails']['Billing']);
            if ($contact_id != null) {
                $args['billingcontact0'] = $contact_id;
            }
        }
        if ($tech_id) {
            $contact_id = $api->updateContact($zoneInfo->handle_updatable, $tech_id, $params['contactdetails']['Tech']);
            if ($contact_id != null) {
                $args['techcontact0'] = $contact_id;
            }
        }
    }

    try {
        if ($args && $zoneInfo->needs_trade && $args['ownercontact0']) {
            $tradeParams['domain'] = $domain;
            $tradeParams = ['ownercontact0' => $args['ownercontact0']];
            if ($params['tld'] == "swiss") {
                $tradeParams['X-SWISS-UID'] = $params['additionalfields']['UID'];
            }
            $api->call('TradeDomain', $tradeParams);
            unset($args['ownercontact0']);
        }
        if ($args) {
            $args['domain'] = $domain;
            $api->call('ModifyDomain', $args);
        }
    } catch (Exception $ex) {
        $values['error'] = $ex->getMessage();
    }

    return $values;
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
function keysystems_CheckAvailability($params)
{
    // TODO need to implement PREMIUM DOMAINS
    try {
        $api = new RRPProxyClient();

        if ($params['isIdnDomain'] && !empty($params['punyCodeSearchTerm'])) {
            $searchTerm = strtolower($params['punyCodeSearchTerm']);
        } else {
            $searchTerm = strtolower($params['searchTerm']);
        }

        $tldsToInclude = $params['tldsToInclude'];
        $results = new ResultsList();

        foreach ($tldsToInclude as $tld) {
            $result = $api->call('CheckDomain', ['domain' => $searchTerm . $tld]);
            $searchResult = new SearchResult($searchTerm, $tld);
            switch ($result['code']) {
                case 210:
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                    break;
                case 211:
                    $status = SearchResult::STATUS_REGISTERED;
                    break;
                default:
                    $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);
            $results->append($searchResult);
        }
        return $results;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
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
function keysystems_GetDomainSuggestions($params)
{
    // TODO need to implement PREMIUM DOMAINS
    try {
        $api = new RRPProxyClient();
        $response = $api->call('GetNameSuggestion', ['name' => $params['searchTerm'], 'show-unavailable' => 0]);
        $results = new ResultsList();
        foreach ($response['property']['name'] as $key => $domain) {
            $d = explode('.', $domain, 2);
            if ($response['property']['availability'][$key] == 'available') {
                $searchResult = new SearchResult($d[0], $d[1]);
                $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
                $results->append($searchResult);
            }
        }
        return $results;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
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
function keysystems_GetRegistrarLock($params)
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
        return ['error' => $ex->getMessage()];
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
function keysystems_SaveRegistrarLock($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', ['domain' => $params['domainname'], 'transferlock' => ($params['lockenabled'] == 'locked') ? 1 : 0]);
        return ['success' => 'success'];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_GetDNS($params)
{
    $values = [];

    $api = new RRPProxyClient();

    try {
        $response = $api->call('CheckDNSZone', ['dnszone' => $params['domainname']]);
        $zoneExists = ($response['code'] != 210);
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }

    if (!$zoneExists) {
        try {
            $api->call('AddDNSZone', ['dnszone' => $params['domainname']]);
            $fields = [
                'domain' => $params['domainname'],
                'nameserver0' => "ns1.dnsres.net",
                'nameserver1' => "ns2.dnsres.net",
                'nameserver2' => "ns3.dnsres.net",
                'nameserver3' => "",
                'nameserver4' => ""
            ];
            $api->call('ModifyDomain', $fields);
        } catch (Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }

    try {
        $response = $api->call('QueryDNSZoneRRList', ['dnszone' => $params['domainname'], 'wide' => 1]);
        $webForwards = [];
        for ($i = 0; $i < $response['property']['count'][0]; $i++) {
            $name = $response['property']['name'][$i];
            $type = $response['property']['type'][$i];
            $content = $response['property']['content'][$i];
            $priority = $response['property']['prio'][$i];
            $source = ($name == "@") ? $params['domainname'] : $name . "." . $params['domainname'];

            if ($response['property']['locked'][$i] == 1) {
                $forward = $api->call('QueryWebFwdList', ['source' => $source, 'wide' => 1]);
                if ($forward['property']['total'][0] > 0 && !in_array($name, $webForwards)) {
                    $webForwards[] = $name;
                    $values[] = ['hostname' => $name, 'type' => $forward['property']['type'][0] == "rd" ? "URL" : "FRAME", 'address' => $forward['property']['target'][0]];
                }
                continue;
            }
            if ($type == 'MX' &&  $content == $priority) {
                continue;
            }

            $values[] = ['hostname' => $name, 'type' => $type, 'address' => $content, 'priority' => $priority];
        }
        return $values;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_SaveDNS($params)
{
    $values = [];
    $oldZone = [];
    $api = new RRPProxyClient();

    try {
        $response = $api->call('QueryDNSZoneRRList', ['dnszone' => $params['domainname'], 'orderby' => "type", 'wide' => 1]);

        for ($i = 0; $i < $response['property']['count'][0]; $i++) {
            $name = $response['property']['name'][$i];
            $type = $response['property']['type'][$i];
            $content = $response['property']['content'][$i];
            $priority = $response['property']['prio'][$i];
            $source = ($name == "@") ? $params['domainname'] : $name . "." . $params['domainname'];

            if ($response['property']['locked'][$i] == 1) {
                $forward = $api->call('QueryWebFwdList', ['source' => $source, 'wide' => 1]);
                if ($forward['property']['total'][0] > 0) {
                    $api->call('DeleteWebFwd', ['source' => $source]);
                }
                continue;
            }

            if ($type == 'MX' && $content == $priority) {
                continue;
            }

            $values = [$name, $response['property']['ttl'][$i], 'IN', $type, $content, $priority];
            if ($priority) {
                unset($values[5]);
            }
            if ($type == 'NS') {
                unset($values[2]);
            }
            $oldZone[] = implode(' ', $values);
        }

        $zone = [];
        $ttl = is_numeric($params['DefaultTTL']) ? $params['DefaultTTL'] : 28800;
        foreach ($params['dnsrecords'] as $record) {
            if (!$record['address']) {
                continue;
            }
            if (!$record['hostname'] || $record['hostname'] == $params['domainname']) {
                $record['hostname'] = "@";
            }

            switch ($record['type']) {
                case "URL":
                case "FRAME":
                    $source = $record['hostname'] == "@" ? $params['domainname'] : $record['hostname'] . '.' . $params['domainname'];
                    $type = ($record['type'] == "URL") ? "RD" : "MRD";
                    $api->call('AddWebFwd', ['source' => $source, 'target' => $record['address'], 'type' => $type]);
                    break;
                case "MX":
                case "SRV":
                    $zone[] = sprintf("%s %s IN %s %s %s", $record['hostname'], $ttl, $record['type'], $record['priority'], $record['address']);
                    break;
                case "NS":
                    $zone[] = sprintf("%s %s %s %s", $record['hostname'], $ttl, $record['type'], $record['address']);
                    break;
                default:
                    $zone[] = sprintf("%s %s IN %s %s", $record['hostname'], $ttl, $record['type'], $record['address']);
            }
        }

        $fields = ['dnszone' => $params['domainname']];
        $i = 0;
        foreach ($oldZone as $record) {
            $fields['DELRR' . $i++] = $record;
        }
        $i = 0;
        foreach ($zone as $record) {
            $fields['ADDRR' . $i++] = $record;
        }
        $api->call('ModifyDNSZone', $fields);
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }

    return $values;
}

/**
 * Get Email Forwardings
 *
 * @param array $params common module parameters
 * @return array
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetEmailForwarding($params)
{
    $api = new RRPProxyClient();
    try {
        $response = $api->call('QueryMailFwdList', ['dnszone' => $params['domainname']]);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
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
function keysystems_SaveEmailForwarding($params)
{
    $api = new RRPProxyClient();
    try {
        $response = $api->call('QueryMailFwdList', ['dnszone' => $params['domainname']]);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
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
function keysystems_IDProtectToggle($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyDomain', ['domain' => $params['domainname'], 'X-WHOISPRIVACY' => ($params["protectenable"]) ? "1" : "0"]);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_GetEPPCode($params)
{
    try {
        $api = new RRPProxyClient();
        $needSetAuthcode = ['de', 'be', 'no', 'eu'];
        if (in_array($params['tld'], $needSetAuthcode)) {
            try {
                $response = $api->call('SetAuthcode', ['domain' => $params['domainname']]);
            } catch (Exception $ex) {
                $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);
            }
        } else {
            $response = $api->call('StatusDomain', ['domain' => $params['domainname']]);
        }

        if (strlen($response["property"]["auth"][0])) {
            return ['eppcode' => htmlspecialchars($response["property"]["auth"][0])];
        } else {
            return ['error' => "No Auth Info code found!"];
        }
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_ReleaseDomain($params)
{
    $fields = [
        'domain' => $params['domainname'],
    ];
    if (!empty($params['transfertag'])) {
        $fields["target"] = $params['transfertag'];
    }

    try {
        $api = new RRPProxyClient();
        $api->call('PushDomain', $fields);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_RequestDelete($params)
{
    $api = new RRPProxyClient();

    if ($params['DeleteMode'] == 'ImmediateIfPossible') {
        try {
            $api->call('DeleteDomain', ['domain' => $params['domainname']]);
            return ['success' => true];
        } catch (Exception $ex) {
            // We revert to AUTODELETE
        }
    }

    try {
        $api->call('SetDomainRenewalmode', ['domain' => $params['domainname'], 'renewalmode' => 'AUTODELETE']);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_RegisterNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('AddNameserver', ['nameserver' => $params['nameserver'], 'ipaddress0' => $params["ipaddress"]]);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_ModifyNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('ModifyNameserver', ['nameserver' => $params['nameserver'], 'delipaddress0' => $params["currentipaddress"], 'addipaddress0' => $params["newipaddress"]]);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_DeleteNameserver($params)
{
    try {
        $api = new RRPProxyClient();
        $api->call('DeleteNameserver', ['nameserver' => $params['nameserver']]);
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_Sync($params)
{
    try {
        $api = new RRPProxyClient();
        $result = $api->call('StatusDomain', ['domain' => $params['sld'] . '.' . $params['tld']]);
        $status = strtolower($result['property']['status'][0]);
        //TODO set admin/tech/billing contacts if necessary
        return [
            'active' => $status == 'active',
            'expired' => $status == 'expired',
            'expirydate' => Carbon::createFromFormat('Y-m-d H:i:s.u', $result['property']['registrationexpirationdate']['0'])->toDateString()
        ];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
function keysystems_TransferSync($params)
{
    $values = [];
    $api = new RRPProxyClient();
    $domain = $params['sld'] . '.' . $params['tld'];

    try {
        $result = $api->call('StatusDomain', ['domain' => $domain]);
        $values['completed'] = true;
    } catch (Exception $ex) {
        try {
            $transfer = $api->call('StatusDomainTransfer', ['domain' => $params['sld'] . '.' . $params['tld']]);
            if (strpos($transfer['property']['transferlog'][count($transfer['property']['transferlog']) - 1], "[SUCCESSFUL]")) {
                $values['completed'] = true;
            } else {
                switch (strtolower($transfer['property']['transferstatus'][0])) {
                    case 'successful':
                        $values['completed'] = true;
                        break;
                    case 'failed':
                        $values['failed'] = true;
                        break;
                }
            }
        } catch (Exception $ex) {
            $values['error'] = $ex->getMessage();
        }
    }

    if ($values['completed']) {
        $values['expirydate'] = Carbon::createFromFormat('Y-m-d H:i:s.u', $result['property']['registrationexpirationdate']['0'])->toDateString();

        $zoneInfo = $api->getZoneInfo($params['tld']);
        if (!$zoneInfo->renews_on_transfer) {
            $values['nextduedate'] = $values['expirydate'];
            $values['nextinvoicedate'] = $values['expirydate'];
        }

        // Enable transfer lock and set Admin/Billing/Tech contacts if needed
        $args = ['transferlock' => 1];

        // Get order
        $order = DB::table('tblorders as o')
            ->join('tbldomains as d', 'd.orderid', 'o.id')
            ->where('d.domain', $domain)
            ->select('o.userid', 'o.contactid', 'o.nameservers')
            ->orderBy('id', 'DESC')
            ->first();

        // Set nameservers if defined in order
        if ($order->nameservers) {
            $nameservers = explode(',', $order->nameservers);
            $i = 0;
            foreach ($nameservers as $nameserver) {
                $args['ownercontact' . $i++] = $nameserver;
            }
        }

        // Set owner contact if missing
        $owner_id = $result['property']['ownercontact'][0];
        if (!$owner_id) {
            $contact = DB::table($order->contactid ? 'tblcontacts' : 'tblclients')
                ->where('id', $order->contactid ? $order->contactid : $order->userid)
                ->select('firstname', 'lastname', 'address1', 'city', 'state', 'country', 'postcode', 'phonenumber', 'companyname')
                ->first();

            if ($contact) {
                $owner_contact = [
                    'firstname' => $contact->firstname,
                    'lastname' => $contact->lastname,
                    'street' => $contact->address1,
                    'city' => $contact->city,
                    'state' => $contact->state,
                    'country' => $contact->country,
                    'zip' => $contact->postcode,
                    'phone' => str_replace(' ', '', $contact->phonenumber),
                    'email' => $contact->email
                ];
                if ($contact->companyname) {
                    $owner_contact['organization'] = $contact->companyname;
                }
                try {
                    $contact = $api->call('AddContact', $owner_contact);
                    $owner_id = $contact['property']['contact'][0];
                    $args['ownercontact0'] = $owner_id;
                } catch (Exception $ex) {
                    $values['error'] = $ex->getMessage();
                }
            }
        }

        // Set admin/billing/tech contacts
        if (!\WHMCS\Config\Setting::getValue('RegistrarAdminUseClientDetails')) {
            $admin_contact = [
                "firstname" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminFirstName"), ENT_QUOTES),
                "lastname" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminLastName"), ENT_QUOTES),
                "street" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminAddress1"), ENT_QUOTES),
                "city" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminCity"), ENT_QUOTES),
                "state" => html_entity_decode(convertStateToCode(
                    \WHMCS\Config\Setting::getValue("RegistrarAdminStateProvince"),
                    \WHMCS\Config\Setting::getValue("RegistrarAdminCountry")
                ), ENT_QUOTES),
                "zip" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminPostalCode"), ENT_QUOTES),
                "country" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminCountry"), ENT_QUOTES),
                "phone" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminPhone"), ENT_QUOTES),
                "email" => html_entity_decode(\WHMCS\Config\Setting::getValue("RegistrarAdminEmailAddress"), ENT_QUOTES)
            ];
            $companyName = \WHMCS\Config\Setting::getValue("RegistrarAdminCompanyName");
            if ($companyName) {
                $admin_contact['organization'] = $companyName;
            }
            $street2 = \WHMCS\Config\Setting::getValue("RegistrarAdminAddress2");
            if (strlen($street2)) {
                $admin_contact["street"] .= " , " . html_entity_decode($street2, ENT_QUOTES);
            }

            try {
                $contact = $api->call('AddContact', $admin_contact);
                $contact_id = $contact['property']['contact'][0];
                $args['admincontact0'] = $params['tld'] == 'it' ? $owner_id : $contact_id;
                $args['billingcontact0'] = $contact_id;
                $args['techcontact0'] = $contact_id;
            } catch (Exception $ex) {
                $values['error'] = $ex->getMessage();
            }
        }

        // Update domain
        try {
            $args['domain'] = $domain;
            $api->call('ModifyDomain', $args);
        } catch (Exception $ex) {
            $values['error'] = $ex->getMessage();
        }
    }

    return $values;
}

/**
 * DNSSEC Management
 *
 * @param array $params common module parameters
 *
 * @return array an array with a template name
 */
function keysystems_dnssec($params)
{
    $api = new RRPProxyClient();
    $error = null;
    $dsData = [];
    $keyData = [];

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

        foreach ($dsdata_rrp as $ds) {
            list($keytag, $alg, $digesttype, $digest) = preg_split('/\s+/', $ds);
            array_push($dsData, ["keytag" => $keytag, "alg" => $alg, "digesttype" => $digesttype, "digest" => $digest]);
        }
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

function keysystems_ConvertPrice($price, $fromCurrency, $toCurrency)
{
    return round($price * keysystems_GetCachedExchangeRate($fromCurrency, $toCurrency));
}

$exchangeRates = [];

function keysystems_GetCachedExchangeRate($from, $to)
{
    global $exchangeRates;

    if ($from == $to) {
        return 1;
    }
    $key = "$from-$to";
    if (!isset($exchangeRates[$key])) {
        $api = new RRPProxyClient();
        try {
            $result = $api->call('QueryExchangeRates', ['currencyfrom' => $from, 'currencyto' => $to, 'limit' => 1]);
        } catch (Exception $ex) {
            die("ERROR getting exchange rate $from - $to : {$ex->getMessage()}\n");
        }
        $exchangeRates[$key] = $result['property']['rate'][0];
    }
    return $exchangeRates[$key];
}

function keysystems_GetTldPricing(array $params)
{
    $ignoreZones = ['nameemail', 'nuidn']; // Those are not real TLDs but the API returns then for some reason

    $pricelist = [];
    $api = new RRPProxyClient();
    try {
        $result = $api->call('QueryZoneList');
    } catch (Exception $e) {
        return ['error' => 'QueryZoneList - ' . $e->getMessage()];
    }

    foreach ($result['property']['zone'] as $id => $zone) {
        if ($result['property']['periodtype'][$id] != "YEAR") {
            continue;
        }
        if (in_array($zone, $ignoreZones)) {
            continue;
        }

        // Determine actual zones
        $domain = $result['property']['3rds'][$id];
        $domains = [];
        if (strpos($domain, ' ') !== false) {
            if (strpos($domain, ',') !== false) {
                $domains = explode(', ', $domain);
            } else {
                if (preg_match('/[a-z\.]/', $zone)) {
                    $domains[] = $zone;
                } else {
                    continue;
                }
            }
        } else {
            $domains[] = $domain;
        }

        foreach ($domains as $domain) {
            $pricelist[strtolower($domain)] = [
                "active" => $result['property']['active'][$id],
                "yearly" => $result['property']['periodtype'][$id] == 'YEAR',
                "count" => $result['property']['domaincount'][$id] ? $result['property']['domaincount'][$id] :  0,
                "currency" => $result['property']['currency'][$id],
                "annual_fee" => is_numeric($result['property']['annual'][$id]) ? $result['property']['annual'][$id] : null,
                "application_fee" => is_numeric($result['property']['application'][$id]) ? $result['property']['application'][$id] : null,
                "restore_fee" => is_numeric($result['property']['restore'][$id]) ? $result['property']['restore'][$id] : null,
                "setup_fee" => is_numeric($result['property']['setup'][$id]) ? $result['property']['setup'][$id] : null,
                "trade_fee" => is_numeric($result['property']['trade'][$id]) ? $result['property']['trade'][$id] : null,
                "transfer_fee" => is_numeric($result['property']['transfer'][$id]) ? $result['property']['transfer'][$id] : null,
            ];
        }
    }

    $defaultCurrency = DB::table('tblcurrencies')->where('default', 1)->value('code');

    $results = new DomainResults();
    foreach ($pricelist as $extension => $values) {
        if (!$values['active'] || !$values['yearly']) {
            continue;
        }

        $zone = $api->getZoneInfo($extension);
        if ($zone == null) {
            continue; // let's ignore this for now...
        }

        preg_match_all("/(?:(\d+)y)+/", $zone->periods, $matches);
        $years = $matches[1];

        if (in_array($values['currency'], array_column(localAPI('GetCurrencies', [])['currencies']['currency'], 'code'))) {
            $currency = $values['currency'];
            $setupFee = $values['setup_fee'];
            $annualFee = $values['annual_fee'];
            $transferFee = $values['transfer_fee'];
            $redemptionFee = $values['restore_fee'];
        } else {
            $currency = $defaultCurrency;
            $setupFee = keysystems_ConvertPrice($values['setup_fee'], $values['currency'], $currency);
            $annualFee = keysystems_ConvertPrice($values['annual_fee'], $values['currency'], $currency);
            $transferFee = keysystems_ConvertPrice($values['transfer_fee'], $values['currency'], $currency);
            $redemptionFee = keysystems_ConvertPrice($values['restore_fee'], $values['currency'], $currency);
        }

        // Workaround for stupid WHMCS logic as of 7.10 RC2
        if ($setupFee > 0) {
            $years = [$years[0]];
        }

        $item = (new ImportItem())
            ->setExtension($extension)
            ->setYears($years)
            ->setRegisterPrice($setupFee + $annualFee)
            ->setCurrency($currency)
            ->setEppRequired($zone->epp_required);

        if (is_numeric($annualFee)) {
            $item->setRenewPrice($annualFee);
        }

        if (is_numeric($transferFee)) {
            $item->setTransferPrice($transferFee);
        }

        if (is_numeric($zone->grace_days)) {
            $item->setGraceFeeDays($zone->grace_days)
                ->setGraceFeePrice($annualFee);
        }
        if (is_numeric($zone->redemption_days)) {
            $item->setRedemptionFeeDays($zone->redemption_days);
            if (is_numeric($redemptionFee)) {
                $item->setRedemptionFeePrice($redemptionFee);
            }
        }

        $results[] = $item;
    }
    return $results;
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 *
 * @return array
 */
function keysystems_ClientAreaCustomButtonArray()
{
    return null;
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @param $params
 * @return array
 */
function keysystems_ClientAreaAllowedFunctions($params)
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
 * @return string HTML Output
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
function keysystems_ClientArea()
{
    return null;
}
