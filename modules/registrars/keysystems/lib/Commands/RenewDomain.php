<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Module\Registrar\RRPproxy\Helpers\ZoneInfo;
use WHMCS\Module\Registrar\RRPproxy\Models\ZoneModel;

/**
 * @see https://wiki.rrpproxy.net/api/api-command/RenewDomain
 */
class RenewDomain extends CommandBase
{
    private ZoneModel $zoneInfo;
    private \WHMCS\Domain\Domain $domain;

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->zoneInfo = ZoneInfo::get($params);
        $this->domain = \WHMCS\Domain\Domain::find($params['domainid']);

        $this->api->args["DOMAIN"] = $this->domainName;
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        if (!$this->zoneInfo->supports_renewals) {
            $renewalMode = new SetDomainRenewalMode($this->params);
            $renewalMode->setRenewOnce();
            $renewalMode->execute();
            return;
        }

        $this->api->args["PERIOD"] = $this->params["regperiod"];
        $expiryDate = $this->domain->expirydate;
        $this->api->args["EXPIRATION"] = $expiryDate->setTimezone('UTC')->year;
        parent::execute();
    }
}
