<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

class ResendNotification extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["TYPE"] = "CONTACTVERIFICATION";
    }

    /**
     * @throws \Exception
     */
    public function execute(): void
    {
        $domain = keysystems_GetDomainInformation($this->params);
        $this->api->args["OBJECT"] = (string)$domain->getRegistrantEmailAddress();
        parent::execute();
    }
}
