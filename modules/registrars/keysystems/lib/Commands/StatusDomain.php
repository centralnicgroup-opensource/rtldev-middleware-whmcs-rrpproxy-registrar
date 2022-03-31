<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Helpers\ZoneInfo;

class StatusDomain extends CommandBase
{
    public bool $isActive;
    public bool $isExpired;
    public bool $isPremium;
    public string $renewalMode;
    /**
     * @var array<string, string>
     */
    public array $nameServers;
    public bool $transferLock;
    public bool $idProtection;
    public string $creationDate;
    public string $expirationDate;
    public string $timeToSuspension;
    public string $authCode;

    public string $ownerContact;
    public string $adminContact;
    public string $billingContact;
    public string $techContact;

    public ?string $vatId;
    public bool $isTrusteeUsed;

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;

        $this->execute();

        $this->isActive = (bool)preg_match("/ACTIVE/i", $this->api->properties["STATUS"][0]);
        $this->isPremium = isset($this->api->properties["X-FEE-CLASS"][0]) && $this->api->properties["X-FEE-CLASS"][0] === "premium";
        $this->renewalMode = $this->api->properties['RENEWALMODE'][0] ?: "DEFAULT";
        $this->setNameServers();
        $this->transferLock = (bool) @$this->api->properties["TRANSFERLOCK"][0];
        $this->idProtection = @$this->api->properties["X-WHOIS-PRIVACY"][0] > 0;
        $this->setTrusteeStatus();
        $this->creationDate = $this->api->castDate($this->api->properties["CREATEDDATE"][0])["long"];
        $this->setExpiryData();
        $this->timeToSuspension = @$this->api->properties["X-TIME-TO-SUSPENSION"][0] ?: "";
        if (strlen($this->api->properties["AUTH"][0]) > 0) {
            $this->authCode = htmlspecialchars($this->api->properties["AUTH"][0]);
        }

        $this->ownerContact = @$this->api->properties["OWNERCONTACT"][0] ?: "";
        $this->adminContact = @$this->api->properties["ADMINCONTACT"][0] ?: "";
        $this->billingContact = @$this->api->properties["BILLINGCONTACT"][0] ?: "";
        $this->techContact = @$this->api->properties["TECHCONTACT"][0] ?: "";

        $this->setRegistrantVatId();
    }

    /**
     * @return void
     */
    private function setNameServers(): void
    {
        $nameservers = [];
        $i = 1;
        foreach ($this->api->properties["NAMESERVER"] as $nameserver) {
            $nameservers["ns" . $i] = $nameserver;
            $i++;
        }
        $this->nameServers = $nameservers;
    }

    /**
     * @return void
     */
    private function setTrusteeStatus(): void
    {
        $keys = array_keys($this->api->properties);
        $names = preg_grep("/^X-.+-ACCEPT-TRUSTEE-TAC$/i", $keys);
        $this->isTrusteeUsed = !empty($names);
    }

    /**
     * @return void
     */
    private function setRegistrantVatId(): void
    {
        $keys = array_keys($this->api->properties);

        $vatId = preg_grep("/VATID/", $keys);
        if ($vatId === false) {
            $this->vatId = null;
            return;
        }
        $names = preg_grep("/ADMIN|TECH|BILLING/", $vatId, PREG_GREP_INVERT);
        if ($names === false) {
            $this->vatId = null;
            return;
        }
        foreach ($names as $prop) {
            if (!empty($this->api->properties[$prop])) {
                $this->vatId = $this->api->properties[$prop][0];
            }
        }
        $this->vatId = null;
    }

    /**
     * Gets proper expiration date for WHMCS
     * @return void
     */
    private function setExpiryData(): void
    {
        $paidUntilDate = $this->api->properties["PAIDUNTILDATE"][0];
        if ($paidUntilDate === null) {
            $ts = 0;
        } else {
            $expirationDate = $this->api->castDate($paidUntilDate);
            if ($this->renewalMode == "RENEWONCE") {
                try {
                    $zoneInfo = ZoneInfo::get($this->params);
                    $periods = ZoneInfo::formatPeriods($zoneInfo->periods);
                } catch (Exception $ex) {
                    $periods = [1];
                }
                $ts = strtotime($expirationDate["long"] . " +$periods[0] year");
            } else {
                $ts = $expirationDate["ts"];
            }
        }
        $this->expirationDate = date("Y-m-d H:i:s", $ts);
        $this->isExpired = strtotime("now") > $ts;
    }
}
