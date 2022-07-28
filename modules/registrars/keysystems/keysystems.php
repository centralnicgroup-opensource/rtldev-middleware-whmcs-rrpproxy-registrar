<?php

/**
 * WHMCS RRPproxy Registrar Module
 *
 * @author Sebastian Vassiliou <sebastian.vassiliou@centralnic.com>
 * @copyright 2020-2022 Key-Systems GmbH
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
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use CNIC\ClientFactory;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\RRPproxy\Commands\AddDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\CheckDomains;
use WHMCS\Module\Registrar\RRPproxy\Commands\CheckDomainTransfer;
use WHMCS\Module\Registrar\RRPproxy\Commands\DeleteDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\GetNameSuggestion;
use WHMCS\Module\Registrar\RRPproxy\Commands\ModifyDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\PushDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\QueryZoneList;
use WHMCS\Module\Registrar\RRPproxy\Commands\RenewDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\ResendNotification;
use WHMCS\Module\Registrar\RRPproxy\Commands\SetAuthCode;
use WHMCS\Module\Registrar\RRPproxy\Commands\SetDomainRenewalMode;
use WHMCS\Module\Registrar\RRPproxy\Commands\StatusAccount;
use WHMCS\Module\Registrar\RRPproxy\Commands\StatusContact;
use WHMCS\Module\Registrar\RRPproxy\Commands\StatusDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\StatusDomainTransfer;
use WHMCS\Module\Registrar\RRPproxy\Commands\TradeDomain;
use WHMCS\Module\Registrar\RRPproxy\Commands\TransferDomain;
use WHMCS\Module\Registrar\RRPproxy\Features\Contact;
use WHMCS\Module\Registrar\RRPproxy\Features\DNSZone;
use WHMCS\Module\Registrar\RRPproxy\Features\MailFwd;
use WHMCS\Module\Registrar\RRPproxy\Features\Nameserver;
use WHMCS\Module\Registrar\RRPproxy\Helpers\Order;
use WHMCS\Module\Registrar\RRPproxy\Helpers\Pricing;
use WHMCS\Module\Registrar\RRPproxy\Helpers\ZoneInfo;
use WHMCS\Module\Registrar\RRPproxy\Migrator;
use WHMCS\Module\Registrar\RRPproxy\Updater;

const RRPPROXY_VERSION = "1.6.0";

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function keysystems_getConfigArray(array $params): array
{
    $msgUpdate = '';
    $msgMigrate = '';
    $msgRegister = "Don't have an RRPproxy Account yet? Get one here: <a target=\"_blank\" href=\"https://www.rrpproxy.net/Register\">www.rrpproxy.net/Register</a>";

    static $dbChecked = false;

    if (isset($params['Username'])) {
        if (!$dbChecked) {
            ZoneInfo::initDb();
            $dbChecked = true;
        }
        if (@$_GET['migrate']) {
            Migrator::migrate($params);
        }
        if (!$params['Username'] && DB::table('tblregistrars')->where('registrar', 'rrpproxy')->exists()) {
            $msgMigrate .= "<br /><a href='configregistrars.php?migrate=true&amp;saved=true#keysystems' class='btn btn-sm btn-primary' title='Click here to automatically migrate domains and TLD prices related to RRPproxy to this module!'>Migrate from old RRPproxy module</a>";
        }
        $updateStatus = Updater::check();
        switch ($updateStatus) {
            case -1:
                $msgUpdate = '<br /><i class="fas fa-times-circle"></i> Unable to check for updates';
                break;
            case 1:
                $msgUpdate = '<br /><i class="fas fa-exclamation-circle"></i> Update available! ';
                $msgUpdate .= '<a class="btn btn-default btn-sm" href="https://github.com/rrpproxy/whmcs-rrpproxy-registrar/releases" target="_blank"><i class="fab fa-github"></i> Get it on GitHub</a>';
                break;
            default:
                $msgUpdate = '<br /><i class="fas fa-check-circle"></i> You are up to date!';
        }
    }

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'RRPproxy v' . RRPPROXY_VERSION
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => $msgRegister . $msgUpdate . $msgMigrate,
        ],
        'Username' => [
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Enter your RRPproxy Username',
        ],
        'Password' => [
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'Enter your RRPproxy Password',
        ],
        'DefaultTTL' => [
            'FriendlyName' => 'Default TTL',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '28800',
            'Description' => 'Default TTL value in seconds for DNS records'
        ],
        "TransferLock" => [
            "FriendlyName" => "Automatic Transfer Lock",
            "Type" => "yesno",
            "Default" => true,
            "Description" => "Automatically locks a Domain after Registration or Transfer"
        ],
        "NSUpdTransfer" => [
            "FriendlyName" => "Automatic NS Update on Transfer",
            "Type" => "yesno",
            "Default" => true,
            "Description" => "Automatically update the domain's nameservers after successful transfer to the ones submitted with the order.<br/>NOTE: By default WHMCS suggests your configured Defaultnameservers in the configuration step of the shopping cart."
        ],
        "AutoDNSManagement" => [
            "FriendlyName" => "Enable DNS Management",
            "Type" => "yesno",
            "Default" => true,
            "Description" => "Enable DNS Management on TLD pricing sync"
        ],
        "AutoEmailForwarding" => [
            "FriendlyName" => "Enable Email Forwarding",
            "Type" => "yesno",
            "Default" => true,
            "Description" => "Enable Email Forwarding on TLD pricing sync"
        ],
        "AutoIDProtection" => [
            "FriendlyName" => "Enable ID Protection",
            "Type" => "yesno",
            "Default" => true,
            "Description" => "Enable ID Protection on TLD pricing sync for compatible TLDs"
        ],
        'RenewProtection' => [
            'FriendlyName' => 'Renewal Protection',
            'Type' => 'yesno',
            "Default" => false,
            'Description' => 'Skips renewal when domain expiration date is already ahead of the new due date'
        ],
        'DeleteMode' => [
            'FriendlyName' => 'Domain deletion mode',
            'Type' => 'dropdown',
            'Options' => ['ImmediateIfPossible', 'AutoDeleteOnExpiry'],
            'Default' => 'ImmediateIfPossible',
        ],
        'DNSSEC' => [
            'FriendlyName' => 'Allow DNSSEC',
            'Type' => 'yesno',
            "Default" => false,
            'Description' => 'Enables DNSSEC configuration in the client area'
        ],
        'DailyCron' => [
            'FriendlyName' => 'Daily Cron',
            'Type' => 'yesno',
            "Default" => false,
            'Description' => 'Makes some daily consistency checks and sends an e-mail report'
        ],
        'TestMode' => [
            'Type' => 'yesno',
            'Description' => 'Tick to enable OT&amp;E',
        ],
        'TestPassword' => [
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'Enter your RRPproxy OT&amp;E Password',
        ],
        "ProxyServer" => [
            "FriendlyName" => "Proxy Server",
            "Type" => "text",
            "Description" => "HTTP(S) Proxy Server (Optional)"
        ]
    ];
}

/**
 * @param array<string, mixed> $params
 * @return Domain
 * @throws Exception
 */
function keysystems_GetDomainInformation(array $params): Domain
{
    $domainStatus = new StatusDomain($params);

    $domain = new Domain();
    $domain->setIsIrtpEnabled(true);
    $domain->setDomain($domainStatus->domainName);
    $domain->setNameservers($domainStatus->nameServers);
    $domain->setTransferLock($domainStatus->transferLock);
    $domain->setExpiryDate(Carbon::createFromFormat("Y-m-d H:i:s", $domainStatus->expirationDate));

    //check contact status
    try {
        $contactStatus = new StatusContact($params, $domainStatus->ownerContact);
        $domain->setRegistrantEmailAddress($contactStatus->email);
        if ($contactStatus->verificationRequested && !$contactStatus->verified) {
            $domain->setDomainContactChangePending(true);
        }
    } catch (Exception $ex) {
        // we suffer in silence...
    }

    if ($domainStatus->timeToSuspension) {
        $domain->setPendingSuspension(true);
        $domain->setDomainContactChangeExpiryDate(Carbon::createFromFormat("Y-m-d H:i:s", $domainStatus->timeToSuspension));
    }
    $domain->setIrtpVerificationTriggerFields([
        "Registrant" => [
            "First Name",
            "Last Name",
            "Organization Name",
            "Email",
        ]
    ]);

    // email forwarding
    try {
        $forwarding = new MailFwd($params);
        $domain->setEmailForwardingStatus(!empty($forwarding->values));
    } catch (Exception $ex) {
        $domain->setEmailForwardingStatus(false);
    }

    // dns management
    try {
        $dnsManagement = new DNSZone($params);
        $domain->setDnsManagementStatus($dnsManagement->exists());
    } catch (Exception $ex) {
        $domain->setDnsManagementStatus(false);
    }

    // id protection
    $domain->setIdProtectionStatus($domainStatus->idProtection);

    // set custom data
    $domain->registrarData = [
        "isPremium" => (int) $domainStatus->isPremium,
        "isTrusteeUsed" => $domainStatus->isTrusteeUsed,
        "registrantTaxId" => $domainStatus->vatId,
        "createdDate" => $domainStatus->creationDate
        //"domainfields" => ... TODO
    ];

    return $domain;
}

/**
 * Resend contact verification e-mail to owner
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function keysystems_ResendIRTPVerificationEmail(array $params): array
{
    try {
        $verify = new ResendNotification($params);
        $verify->execute();
        return ["success" => true];
    } catch (Exception $ex) {
        return ["error" => $ex->getMessage()];
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @throws Exception
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_RegisterDomain(array $params): array
{
    $params = injectDomainObjectIfNecessary($params);
    $register = new AddDomain($params);
    try {
        $register->execute();
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_TransferDomain(array $params): array
{
    try {
        // Domain transfer pre-check (apparently only for com/net/jobs/org/bin/info/name/mobi)
        $check = new CheckDomainTransfer($params);
        $check->execute();
        if (!$check->wasSuccessful()) {
            return ["error" => $check->getErrors()];
        }

        $transfer = new TransferDomain($params);
        $transfer->execute();
        if (!empty($transfer->getErrors())) {
            return ["error" => $transfer->getErrors()];
        }
        return ["success" => true];
    } catch (Exception $ex) {
        return ["error" => $ex->getMessage()];
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_RenewDomain(array $params): array
{
    try {
        $renew = new RenewDomain($params);
        $renew->execute();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetNameservers(array $params): array
{
    try {
        $domain = new StatusDomain($params);
        return $domain->nameServers;
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_SaveNameservers(array $params): array
{
    try {
        $domain = new ModifyDomain($params);
        $domain->setNameServers();
        $domain->execute();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetContactDetails(array $params): array
{
    try {
        $domain = new StatusDomain($params);

        $contacts['Registrant'] = Contact::getContactInfo($domain->ownerContact, $params);

        if (\WHMCS\Config\Setting::getValue('RegistrarAdminUseClientDetails')) {
            if ($domain->adminContact) {
                $contacts['Admin'] = Contact::getContactInfo($domain->adminContact, $params);
            }
            if ($domain->billingContact) {
                $contacts['Billing'] = Contact::getContactInfo($domain->billingContact, $params);
            }
            if ($domain->techContact) {
                $contacts['Tech'] = Contact::getContactInfo($domain->techContact, $params);
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @throws Exception
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_SaveContactDetails(array $params): array
{
    $values = [];

    try {
        $statusDomain = new StatusDomain($params);
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }

    $owner_id = $statusDomain->api->properties['OWNERCONTACT'][0];
    $admin_id = $statusDomain->api->properties['ADMINCONTACT'][0];
    $bill_id = $statusDomain->api->properties['BILLINGCONTACT'][0];
    $tech_id = $statusDomain->api->properties['TECHCONTACT'][0];

    $modifyDomain = new ModifyDomain($params);
    $zoneInfo = ZoneInfo::get($params);

    $contact_id = Contact::updateContact($zoneInfo->handle_updatable, $owner_id, $params['contactdetails']['Registrant'], $params);
    if ($contact_id != null) {
        $modifyDomain->setOwnerContact($contact_id);
    }
    if (\WHMCS\Config\Setting::getValue('RegistrarAdminUseClientDetails')) {
        if ($admin_id) {
            $contact_id = Contact::updateContact($zoneInfo->handle_updatable, $admin_id, $params['contactdetails']['Admin'], $params);
            if ($contact_id != null) {
                $modifyDomain->setAdminContact($contact_id);
            }
        }
        if ($bill_id) {
            $contact_id = Contact::updateContact($zoneInfo->handle_updatable, $bill_id, $params['contactdetails']['Billing'], $params);
            if ($contact_id != null) {
                $modifyDomain->setBillingContact($contact_id);
            }
        }
        if ($tech_id) {
            $contact_id = Contact::updateContact($zoneInfo->handle_updatable, $tech_id, $params['contactdetails']['Tech'], $params);
            if ($contact_id != null) {
                $modifyDomain->setTechContact($contact_id);
            }
        }
    }

    try {
        if (count($modifyDomain->api->args) > 1 && $zoneInfo->needs_trade && $modifyDomain->api->args['OWNERCONTACT0']) {
            $tradeDomain = new TradeDomain($params, $modifyDomain->api->args['OWNERCONTACT0']);
            $tradeDomain->execute();
            unset($modifyDomain->api->args['OWNERCONTACT0']);
        }
        $modifyDomain->execute();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, string>|ResultsList<SearchResult> An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 * @throws Exception Upon domain availability check failure.
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 */
function keysystems_CheckAvailability(array $params)
{
    // TODO need to implement PREMIUM DOMAINS
    try {
        $tldsToInclude = $params['tldsToInclude'];
        $results = new ResultsList();

        foreach (array_chunk($tldsToInclude, 32) as $tlds) {
            $checkDomains = new CheckDomains($params, $tlds);
            $checkDomains->execute();
            $i = 0;
            foreach ($checkDomains->getResults() as $searchResult) {
                switch (substr($checkDomains->api->properties["DOMAINCHECK"][$i++], 0, 3)) {
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
        }
        return $results;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Get Domain Suggestions.
 *
 * Provide domain suggestions based on the domain lookup term provided.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, string>|ResultsList<SearchResult> An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 * @throws Exception Upon domain suggestions check failure.
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 */
function keysystems_GetDomainSuggestions(array $params)
{
    // TODO need to implement PREMIUM DOMAINS
    try {
        $getNameSuggestion = new GetNameSuggestion($params);
        $getNameSuggestion->execute();
        $results = new ResultsList();
        foreach ($getNameSuggestion->api->properties["NAME"] as $key => $domain) {
            $d = explode('.', $domain, 2);
            if ($getNameSuggestion->api->properties["AVAILABILITY"][$key] == 'available') {
                $searchResult = new SearchResult($d[0], $d[1]);
                $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
                $results->append($searchResult);
            }
        }
        return $results;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Get registrar lock status.
 *
 * Also known as Domain Lock or Transfer Lock status.
 *
 * @param array<string, mixed> $params common module parameters
 * @return string|array<string, string> Lock status or error message
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetRegistrarLock(array $params)
{
    try {
        $domain = new StatusDomain($params);
        return $domain->transferLock ? "locked" : "unlocked";
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Set registrar lock status.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_SaveRegistrarLock(array $params): array
{
    try {
        $domain = new ModifyDomain($params);
        $domain->setRegistrarLock();
        $domain->execute();
        return ['success' => 'success'];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed> DNS Host Records
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetDNS(array $params): array
{
    try {
        $dns = new DNSZone($params);
        return $dns->get();
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Update DNS Host Records.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_SaveDNS(array $params): array
{
    try {
        $dns = new DNSZone($params);
        $dns->save();
        return [];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Get Email Forwardings
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetEmailForwarding(array $params): array
{
    try {
        $emailFwd = new MailFwd($params);
        return $emailFwd->values;
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Save Email Forwarding
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_SaveEmailForwarding(array $params): array
{
    try {
        $emailFwd = new MailFwd($params);
        $emailFwd->update();
        return [];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Enable/Disable ID Protection.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_IDProtectToggle(array $params): array
{
    try {
        $domain = new ModifyDomain($params);
        $domain->setWhoisPrivacy();
        $domain->execute();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_GetEPPCode(array $params): array
{
    try {
        $authCode = null;
        if (in_array($params["tld"], ["de", "be", "no", "eu"])) {
            try {
                $auth = new SetAuthCode($params);
                $authCode = $auth->getAuthCode();
            } catch (Exception $ex) {
                // We suffer in silence
            }
        }
        if (!$authCode) {
            $status = new StatusDomain($params);
            $authCode = $status->authCode;
        }

        if ($authCode) {
            return ['eppcode' => $authCode];
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_ReleaseDomain(array $params): array
{
    try {
        $release = new PushDomain($params);
        $release->execute();
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Delete Domain.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_RequestDelete(array $params): array
{
    if ($params["DeleteMode"] == "ImmediateIfPossible") {
        try {
            $delete = new DeleteDomain($params);
            $delete->execute();
            return ['success' => true];
        } catch (Exception $ex) {
            // We fallback to setting renewal mode
        }
    }

    try {
        $renewalMode = new SetDomainRenewalMode($params);
        $renewalMode->setAutoDelete();
        $renewalMode->execute();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_RegisterNameserver(array $params): array
{
    try {
        $ns = new Nameserver($params);
        $ns->add();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_ModifyNameserver(array $params): array
{
    try {
        $ns = new Nameserver($params);
        $ns->modify();
        return ['success' => true];
    } catch (Exception $ex) {
        return ['error' => $ex->getMessage()];
    }
}

/**
 * Delete a Nameserver.
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_DeleteNameserver(array $params): array
{
    try {
        $ns = new Nameserver($params);
        $ns->delete();
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_Sync(array $params): array
{
    try {
        $domain = new StatusDomain($params);
        //TODO set admin/tech/billing contacts if necessary
        return [
            'active' => $domain->isActive,
            'expired' => $domain->isExpired,
            'expirydate' => Carbon::createFromFormat('Y-m-d H:i:s', $domain->expirationDate)->toDateString()
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
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed>
 * @throws Exception
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 */
function keysystems_TransferSync(array $params): array
{
    $values = [];

    try {
        $domain = new StatusDomain($params);
        $values['completed'] = true;
    } catch (Exception $ex) {
        try {
            $transfer = new StatusDomainTransfer($params);
            if ($transfer->hasFailed()) {
                $values['failed'] = true;
                $values['reason'] = $transfer->getLog();
            } else {
                $values['completed'] = false;
            }
        } catch (Exception $ex) {
            $values['error'] = 'StatusDomainTransfer: ' . $ex->getMessage();
        }
        return $values;
    }

    $values['expirydate'] = Carbon::createFromFormat('Y-m-d H:i:s', $domain->expirationDate)->toDateString();

    $zoneInfo = ZoneInfo::get($params);
    if (!$zoneInfo->renews_on_transfer) {
        $values['nextduedate'] = $values['expirydate'];
        $values['nextinvoicedate'] = $values['expirydate'];
    }

    $modifyDomain = new ModifyDomain($params);
    $modifyDomain->setRegistrarLock();

    // Get order
    $order = new Order($modifyDomain->domainName);

    // Set nameservers
    if ($params["NSUpdTransfer"] == "on" && $order->nameServers) {
        $existingNameservers = $domain->nameServers;
        sort($existingNameservers);
        $orderNameservers = $order->nameServers;
        sort($orderNameservers);
        $diffNameservers = array_udiff($orderNameservers, $existingNameservers, "strcasecmp");
        if (count($diffNameservers) > 0) {
            $i = 1;
            foreach ($orderNameservers as $nameserver) {
                if (!$nameserver) {
                    continue;
                }
                $modifyDomain->params["ns" . $i++] = $nameserver;
            }
            $modifyDomain->setNameServers();
        }
    }

    // Set owner contact if missing
    $owner_id = $domain->ownerContact;
    if (!$owner_id) {
        $owner_contact = $order->getContact();
        if ($owner_contact) {
            if (is_object($owner_contact)) {
                $owner_contact = get_object_vars($owner_contact);
            }
            try {
                $owner_id = Contact::getOrCreateOwnerContact($owner_contact, $params);
                $modifyDomain->setOwnerContact($owner_id);
            } catch (Exception $ex) {
                //TODO use module logging instead
                localAPI('LogActivity', ['description' => "[keysystems] getOrCreateOwnerContact on TransferSync failed: {$ex->getMessage()}"]);
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
            $contact_id = Contact::getOrCreateContact($admin_contact, $params);
            $modifyDomain->setAdminContact($params['tld'] == 'it' ? $owner_id : $contact_id);
            $modifyDomain->setBillingContact($contact_id);
            $modifyDomain->setTechContact($contact_id);
        } catch (Exception $ex) {
            //TODO use module logging instead
            localAPI('LogActivity', ['description' => "[keysystems] getOrCreateContact on TransferSync failed: {$ex->getMessage()}"]);
        }
    }

    // Update domain
    try {
        $modifyDomain->execute();
    } catch (Exception $ex) {
        //TODO use module logging instead
        localAPI('LogActivity', ['description' => "[keysystems] ModifyDomain on TransferSync failed: {$ex->getMessage()}"]);
    }

    return $values;
}

/**
 * DNSSEC Management
 *
 * @param array<string, mixed> $params common module parameters
 * @return array<string, mixed> an array with a template name
 */
function keysystems_dnssec(array $params): array
{
    $error = null;
    $dsData = [];
    $keyData = [];

    try {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["DNSSEC"])) {
            $modifyDomain = new ModifyDomain($params);
            $modifyDomain->setDnssecRecords($_POST["DNSSEC"]);
            if (count($modifyDomain->api->args) <= 1) {
                $modifyDomain->setDnssecDelete();
            }
            $modifyDomain->execute();
        }

        $statusDomain = new StatusDomain($params);

        $dsdata_rrp = (isset($statusDomain->api->properties["DNSSECDSDATA"])) ? $statusDomain->api->properties["DNSSECDSDATA"] : [];
        $keydata_rrp = (isset($statusDomain->api->properties["DNSSEC"])) ? $statusDomain->api->properties["DNSSEC"] : [];

        foreach ($dsdata_rrp as $ds) {
            $split = preg_split('/\s+/', $ds);
            if ($split === false) {
                continue;
            }
            list($keytag, $alg, $digesttype, $digest) = $split;
            $dsData[] = ["keytag" => $keytag, "alg" => $alg, "digesttype" => $digesttype, "digest" => $digest];
        }
        foreach ($keydata_rrp as $key) {
            $split = preg_split('/\s+/', $key);
            if ($split === false) {
                continue;
            }
            list($flags, $protocol, $alg, $pubkey) = $split;
            $keyData[] = ["flags" => $flags, "protocol" => $protocol, "alg" => $alg, "pubkey" => $pubkey];
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }

    return [
        "templatefile" => "dnssec",
        "vars" => [
            "flagOptions" => [
                256 => "Zone Signing Key",
                257 => "Key Signing Key"
            ],
            "algOptions" => [
                8 => "RSA/SHA256",
                10 => "RSA/SHA512",
                12 => "GOST R 34.10-2001",
                13 => "ECDSA/SHA-256",
                14 => "ECDSA/SHA-384",
                15 => "Ed25519",
                16 => "Ed448"
            ],
            "digestOptions" => [
                2 => "SHA-256",
                3 => "GOST R 34.11-94",
                4 => "SHA-384"
            ],
            "dsdata" => $dsData,
            "ksdata" => $keyData,
            "successful" => ($_SERVER["REQUEST_METHOD"] === "POST" && $error == null),
            "error" => $error
        ]
    ];
}

/**
 * @param array<string, mixed> $params
 * @return array<string, string>|ResultsList<ImportItem>
 * @throws Exception
 */
function keysystems_GetTldPricing(array $params)
{
    $results = new ResultsList();
    $ignoreZones = ["nameemail", "nuidn"]; // Those are not real TLDs but the API returns them for the below reasons
    // .nu idns e.g. omvärlden.nu (so <idn>.nu vs <ascii>.nu)
    // nameemail -> https://wiki.hexonet.net/wiki/NAME#.NAME_Email_Forwardings
    $defaultCurrency = DB::table('tblcurrencies')->where('default', 1)->value('code');

    try {
        $zoneList = new QueryZoneList($params);
        $zoneList->execute();
    } catch (Exception $ex) {
        return ["error" => "QueryZoneList - " . $ex->getMessage()];
    }

    foreach ($zoneList->api->properties["ZONE"] as $id => $zone) {
        if ($zoneList->api->properties["PERIODTYPE"][$id] != "YEAR") {
            continue;
        }
        if (in_array($zone, $ignoreZones)) {
            continue;
        }

        try {
            $zoneInfo = ZoneInfo::get($params, $zone);
        } catch (Exception $ex) {
            continue; // let's ignore this for now...
        }

        // Determine actual zones
        $tldList = $zoneList->api->properties["3RDS"][$id];
        $tlds = [];
        if (strpos($tldList, ' ') !== false) {
            if (strpos($tldList, ',') !== false) {
                $tlds = explode(', ', $tldList);
            } else {
                if (preg_match('/[a-z\.]/', $zone)) {
                    $tlds[] = $zone;
                } else {
                    continue;
                }
            }
        } else {
            $tlds[] = $tldList;
        }

        $idn = $zoneList->api->client->IDNConvert($tlds);

        foreach ($tlds as $index => $tld) {
            $extension = strtolower($idn[$index]["IDN"]);
            $values = [
                "active" => $zoneList->api->properties["ACTIVE"][$id],
                "yearly" => $zoneList->api->properties["PERIODTYPE"][$id] == "YEAR",
//                "count" => $zoneList->api->properties["DOMAINCOUNT"][$id] ?:  0,
                "currency" => $zoneList->api->properties["CURRENCY"][$id],
                "annual_fee" => (float) $zoneList->api->properties["ANNUAL"][$id],
//                "application_fee" => (float) $zoneList->api->properties["APPLICATION"][$id],
                "restore_fee" => (float) $zoneList->api->properties["RESTORE"][$id],
                "setup_fee" => (float) $zoneList->api->properties["SETUP"][$id],
//                "trade_fee" => (float) $zoneList->api->properties["TRADE"][$id],
                "transfer_fee" => (float) $zoneList->api->properties["TRANSFER"][$id]
            ];

            if (!$values['active'] || !$values['yearly']) {
                continue;
            }

            preg_match_all("/(?:(\d+)y)+/", $zoneInfo->periods, $matches);
            $years = $matches[1];

            $systemCurrencies = localAPI('GetCurrencies', [])['currencies']['currency'];
            if (in_array($values['currency'], array_column($systemCurrencies, 'code'))) {
                $currency = $values['currency'];
                $setupFee = $values['setup_fee'];
                $annualFee = $values['annual_fee'];
                $transferFee = $values['transfer_fee'];
                $redemptionFee = $values['restore_fee'];
            } else {
                $currency = $defaultCurrency;
                try {
                    $setupFee = Pricing::convertPrice($params, $values['setup_fee'], $values['currency'], $currency);
                    $annualFee = Pricing::convertPrice($params, $values['annual_fee'], $values['currency'], $currency);
                    $transferFee = Pricing::convertPrice($params, $values['transfer_fee'], $values['currency'], $currency);
                    $redemptionFee = Pricing::convertPrice($params, $values['restore_fee'], $values['currency'], $currency);
                } catch (Exception $ex) {
                    continue; // currency blocked - so skip this tld
                }
            }

            // Workaround for stupid WHMCS logic as of 7.10 RC2
            if ($setupFee > 0) {
                $years = [$years[0]];
            }

            $item = (new ImportItem())
                ->setExtension($extension)
                ->setYears($years)
                ->setRegisterPrice($setupFee + $annualFee)
                ->setRenewPrice($annualFee)
                ->setTransferPrice($transferFee)
                ->setCurrency($currency)
                ->setEppRequired($zoneInfo->epp_required);

            if (is_numeric($zoneInfo->grace_days)) {
                $item->setGraceFeeDays($zoneInfo->grace_days)
                    ->setGraceFeePrice($annualFee);
            }
            if (is_numeric($zoneInfo->redemption_days)) {
                $item->setRedemptionFeeDays($zoneInfo->redemption_days);
                $item->setRedemptionFeePrice($redemptionFee);
            }

            $results->append($item);
        }
    }

    Pricing::syncFeatures($params);

    return $results;
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 *
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function keysystems_ClientAreaCustomButtonArray(array $params): array
{
    if ($params['DNSSEC'] == 'on') {
        return ["DNSSEC Management" => "dnssec"];
    }
    return [];
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function keysystems_ClientAreaAllowedFunctions(array $params): array
{
    if ($params['DNSSEC'] == 'on') {
        return ["DNSSEC Management" => "dnssec"];
    }
    return [];
}

/**
 * Client Area Output.
 *
 * This function renders output to the domain details interface within
 * the client area. The return should be the HTML to be output.
 *
 * @return string|null HTML Output
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 */
//function keysystems_ClientArea(): ?string
//{
//    return null;
//}

/**
 * Return Zone Configuration / Feature data
 * @param array<string, mixed> $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 * @return object|null
 */
function keysystems_GetZoneFeatures(array $params): ?object
{
    return ZoneInfo::getForMigrator($params);
}

/**
 * Returns customer account details such as amount, currency, deposit etc.
 *
 * @return array<string, mixed>
 */
function keysystems_getAccountDetails(): array
{
    try {
        $statusAccount = new StatusAccount([]);
        return [
            "success" => true,
            "amount" => $statusAccount->api->properties["AMOUNT"][0],
            "currency" => $statusAccount->api->properties["CURRENCY"][0]
        ];
    } catch (Exception $e) {
        return [
            "success" => false
        ];
    }
}
