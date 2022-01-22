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
     * @return void
     */
    public function setNameServers(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->api->args["NAMESERVER" . ($i - 1)] = $this->params["ns$i"];
        }
    }

    /**
     * @return void
     */
    public function setRegistrarLock(): void
    {
        if (isset($this->params['lockenabled'])) {
            $this->api->args["TRANSFERLOCK"] = (int) $this->params['lockenabled'] == "locked";
        } elseif (isset($this->params['TransferLock'])) {
            $this->api->args["TRANSFERLOCK"] = (int) $this->params["TransferLock"] == "on";
        } else {
            $this->api->args["TRANSFERLOCK"] = 1;
        }
    }

    /**
     * @return void
     */
    public function setWhoisPrivacy(): void
    {
        $this->api->args["X-WHOISPRIVACY"] = (int) $this->params["protectenable"];
    }

    /**
     * @param string $contactHandle
     * @return void
     */
    public function setOwnerContact(string $contactHandle): void
    {
        $this->api->args["OWNERCONTACT0"] = $contactHandle;
    }

    /**
     * @param string $contactHandle
     * @return void
     */
    public function setAdminContact(string $contactHandle): void
    {
        $this->api->args["ADMINCONTACT0"] = $contactHandle;
    }

    /**
     * @param string $contactHandle
     * @return void
     */
    public function setBillingContact(string $contactHandle): void
    {
        $this->api->args["BILLINGCONTACT0"] = $contactHandle;
    }

    /**
     * @param string $contactHandle
     * @return void
     */
    public function setTechContact(string $contactHandle): void
    {
        $this->api->args["TECHCONTACT0"] = $contactHandle;
    }

    /**
     * @param array<array<string>> $records
     * @return void
     */
    public function setDnssecRecords(array $records): void
    {
        $i = 0;
        foreach ($records as $record) {
            $record = array_map('trim', $record);
            if (!in_array('', $record)) {
                $this->api->args["DNSSEC" . $i++] = implode(" ", $record);
            }
        }
    }

    /**
     * @return void
     */
    public function setDnssecDelete(): void
    {
        $this->api->args['DNSSECDELALL'] = 1;
    }
}
