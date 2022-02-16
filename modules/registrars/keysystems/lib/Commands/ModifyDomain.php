<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class ModifyDomain extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        if (count($this->api->args) > 1) {
            parent::execute();
        }
    }

    /**
     * @return $this
     */
    public function setNameServers(): ModifyDomain
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->api->args["NAMESERVER" . ($i - 1)] = $this->params["ns$i"];
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function setRegistrarLock(): ModifyDomain
    {
        if (isset($this->params['lockenabled'])) {
            $this->api->args["TRANSFERLOCK"] = (int) $this->params['lockenabled'] == "locked";
        } elseif (isset($this->params['TransferLock'])) {
            $this->api->args["TRANSFERLOCK"] = (int) $this->params["TransferLock"] == "on";
        } else {
            $this->api->args["TRANSFERLOCK"] = 1;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function setWhoisPrivacy(): ModifyDomain
    {
        $this->api->args["X-WHOISPRIVACY"] = (int) $this->params["protectenable"];
        return $this;
    }

    /**
     * @param string $contactHandle
     * @return $this
     */
    public function setOwnerContact(string $contactHandle): ModifyDomain
    {
        $this->api->args["OWNERCONTACT0"] = $contactHandle;
        return $this;
    }

    /**
     * @param string $contactHandle
     * @return $this
     */
    public function setAdminContact(string $contactHandle): ModifyDomain
    {
        $this->api->args["ADMINCONTACT0"] = $contactHandle;
        return $this;
    }

    /**
     * @param string $contactHandle
     * @return $this
     */
    public function setBillingContact(string $contactHandle): ModifyDomain
    {
        $this->api->args["BILLINGCONTACT0"] = $contactHandle;
        return $this;
    }

    /**
     * @param string $contactHandle
     * @return $this
     */
    public function setTechContact(string $contactHandle): ModifyDomain
    {
        $this->api->args["TECHCONTACT0"] = $contactHandle;
        return $this;
    }

    /**
     * @param array<array<string>> $records
     * @return $this
     */
    public function setDnssecRecords(array $records): ModifyDomain
    {
        $i = 0;
        foreach ($records as $record) {
            $record = array_map('trim', $record);
            if (!in_array('', $record)) {
                $this->api->args["DNSSEC" . $i++] = implode(" ", $record);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function setDnssecDelete(): ModifyDomain
    {
        $this->api->args['DNSSECDELALL'] = 1;
        return $this;
    }
}
