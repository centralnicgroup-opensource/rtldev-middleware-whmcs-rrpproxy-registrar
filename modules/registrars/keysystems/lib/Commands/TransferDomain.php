<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Features\Contact;
use WHMCS\Module\Registrar\RRPproxy\Helpers\AdditionalFields;
use WHMCS\Module\Registrar\RRPproxy\Helpers\ZoneInfo;
use WHMCS\Module\Registrar\RRPproxy\Models\ZoneModel;

class TransferDomain extends CommandBase
{
    private ZoneModel $zoneInfo;

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->zoneInfo = ZoneInfo::get($params);

        $this->api->args["DOMAIN"] = $this->domainName;
        $this->api->args["ACTION"] = "REQUEST";
        if ($this->zoneInfo->renews_on_transfer) {
            $this->api->args["PERIOD"] = $params["regperiod"];
        }
        if ($params["eppcode"]) {
            $this->api->args["AUTH"] = $params["eppcode"];
        }
//        Disabled as for example .it does not like this
//        if ($this->zoneInfo->needs_trade) {
//            $this->api->args["ACCEPT-TRADE"] = 1;
//        }
        $this->api->args["TRANSFERLOCK"] = 1;
        $this->api->args["FORCEREQUEST"] = 1; // TODO what does this do?

        // Handle nameservers
        foreach ($params as $key => $n) {
            if (preg_match("/^ns([0-9]+)$/", $key, $m)) {
                $i = (int)$m[1] - 1;
                $this->api->args["NAMESERVER" . $i] = $n;
            }
        }

        // Handle contacts
        $isAfnic = preg_match("/\.(fr|pm|re|tf|wf|yt)$/i", $this->domainName);
        $isAu = preg_match("/\.au$/i", $this->domainName);
        $isCaUs = preg_match("/\.(ca|us)$/i", $this->domainName);
        if ($isAfnic || $isAu || (!$isCaUs && !$this->zoneInfo->needs_trade)) {
            $contactId = Contact::getOrCreateOwnerContact($params, $params);
            if (!$isAfnic) {
                $this->api->args["OWNERCONTACT0"] = $contactId;
                $this->api->args["BILLINGCONTACT0"] = $contactId;
            }
            $this->api->args["ADMINCONTACT0"] = $contactId;
            $this->api->args["TECHCONTACT0"] = $contactId;
        }

        // Handle additional fields
        $fields = new AdditionalFields($params["domainObj"]->getLastTLDSegment());
        foreach ($fields->fields as $key => $val) {
            $this->api->args[$key] = $val;
        }
        if (preg_match("/\.(ca|ro)$/i", $this->domainName)) {
            unset($this->api->args["X-CA-DISCLOSE"]); // not supported for transfers
        }
        if (preg_match("/\.ngo$/i", $this->domainName)) {
            unset($this->api->args["X-NGO-ACCEPT-REGISTRATION-TAC"]); // not supported for transfers
        }
    }
}
