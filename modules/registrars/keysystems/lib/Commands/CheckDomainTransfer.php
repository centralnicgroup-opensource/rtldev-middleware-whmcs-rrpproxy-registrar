<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class CheckDomainTransfer extends CommandBase
{
    public bool $userTransferRequired = false;

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
            return;
        }
        if (isset($this->api->properties["AUTHISVALID"]) && $this->api->properties["AUTHISVALID"][0] === "NO") {
            $this->setError("Invaild Authorization Code");
            return;
        }
        if (isset($this->api->properties["TRANSFERLOCK"]) && $this->api->properties["TRANSFERLOCK"][0] === "1") {
            $this->setError("Transferlock is active. Therefore the Domain cannot be transferred.");
            return;
        }
        if (isset($this->api->properties["USERTRANSFERREQUIRED"]) && $this->api->properties["USERTRANSFERREQUIRED"][0] === "1") {
            $this->userTransferRequired = true;
        }
    }
}
