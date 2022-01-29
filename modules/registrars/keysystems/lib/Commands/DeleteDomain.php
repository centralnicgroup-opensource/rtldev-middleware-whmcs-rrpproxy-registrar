<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

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
}
