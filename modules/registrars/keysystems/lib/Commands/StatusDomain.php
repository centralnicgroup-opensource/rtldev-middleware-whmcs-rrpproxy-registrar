<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class StatusDomain extends CommandBase
{
    public string $status;
    public bool $isPremium;
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

        $this->status = strtolower($this->api->properties['STATUS'][0]);
        $this->isPremium = isset($this->api->properties["X-FEE-CLASS"][0]) && $this->api->properties["X-FEE-CLASS"][0] === "premium";
        $this->nameServers = $this->getNameServers();
        $this->transferLock = (bool) @$this->api->properties["TRANSFERLOCK"][0];
        $this->idProtection = @$this->api->properties["X-WHOIS-PRIVACY"][0] > 0;
        $this->isTrusteeUsed = $this->getTrusteeStatus();
        $this->creationDate = $this->api->properties["CREATEDDATE"][0];
        $this->expirationDate = $this->api->properties["REGISTRATIONEXPIRATIONDATE"][0];
        $this->timeToSuspension = @$this->api->properties["X-TIME-TO-SUSPENSION"][0] ?: "";
        if (strlen($this->api->properties["AUTH"][0]) > 0) {
            $this->authCode = htmlspecialchars($this->api->properties["AUTH"][0]);
        }

        $this->ownerContact = @$this->api->properties["OWNERCONTACT"][0] ?: "";
        $this->adminContact = @$this->api->properties["ADMINCONTACT"][0] ?: "";
        $this->billingContact = @$this->api->properties["BILLINGCONTACT"][0] ?: "";
        $this->techContact = @$this->api->properties["TECHCONTACT"][0] ?: "";

        $this->vatId = $this->getRegistrantVatId();
    }

    /**
     * @return array<string, string>
     */
    private function getNameServers(): array
    {
        $nameservers = [];
        $i = 1;
        foreach ($this->api->properties["NAMESERVER"] as $nameserver) {
            $nameservers["ns" . $i] = $nameserver;
            $i++;
        }
        return $nameservers;
    }

    /**
     * @return bool
     */
    private function getTrusteeStatus(): bool
    {
        $keys = array_keys($this->api->properties);
        $names = preg_grep("/^X-.+-ACCEPT-TRUSTEE-TAC$/i", $keys);
        return !empty($names);
    }

    /**
     * @return string|null
     */
    private function getRegistrantVatId(): ?string
    {
        $keys = array_keys($this->api->properties);

        $vatId = preg_grep("/VATID/", $keys);
        if ($vatId === false) {
            return null;
        }
        $names = preg_grep("/ADMIN|TECH|BILLING/", $vatId, PREG_GREP_INVERT);
        if ($names === false) {
            return null;
        }
        foreach ($names as $prop) {
            if (!empty($this->api->properties[$prop])) {
                return $this->api->properties[$prop];
            }
        }
        return null;
    }
}
