<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class DeleteDomain extends CommandBase
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
        try {
            if ($this->params["DeleteMode"] == "ImmediateIfPossible") {
                parent::execute();
                return;
            }
        } catch (Exception $ex) {
            // We revert to AUTODELETE
        }

        $this->api->args["RENEWALMODE"] = "AUTODELETE";
        $this->setCommandName("SetDomainRenewalmode");
        parent::execute();
    }
}
