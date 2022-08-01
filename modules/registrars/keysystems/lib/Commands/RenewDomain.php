<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Module\Registrar\Keysystems\Helpers\ZoneInfo;
use WHMCS\Module\Registrar\Keysystems\Models\ZoneModel;
use Illuminate\Database\Capsule\Manager as DB;

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
        // Fake renewal if current expiration date is already what renewal expects
        if ($this->params['RenewProtection']) {
            $dueDate = DB::table('tbldomains')
                ->where('domain', '=', $this->domainName)
                ->value('nextduedate');
            if ($this->domain->expirydate >= $dueDate) {
                $msg = "Renewal for domain $this->domainName was skipped because the domain was already renewed.\n";
                $msg .= "Current expiration date: $this->domain->expirydate\n";

                localAPI('LogActivity', ['description' => "[CentralNicReseller] $msg"]);

                $command = "sendadminemail";
                $values["customsubject"] = "CentralNic Reseller Renewal Skipped";
                $values["custommessage"] = nl2br($msg);
                $values["type"] = "system";
                $values["mergefields"] = [];
                $values["deptid"] = 0;

                localAPI($command, $values);
                return;
            }
        }

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
