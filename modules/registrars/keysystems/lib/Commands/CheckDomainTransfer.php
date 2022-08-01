<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

class CheckDomainTransfer extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;
        if ($this->params["eppcode"]) {
            $this->api->args["AUTH"] = $this->params["eppcode"];
        }
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        parent::execute();

        if ($this->api->response["CODE"] !== "218") {
            $this->setError($this->api->response["DESCRIPTION"]);
        }
    }
}
