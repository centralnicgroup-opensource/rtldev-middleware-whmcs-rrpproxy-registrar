<?php

namespace WHMCS\Module\Registrar\RRPproxy\Features;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Commands\CommandBase;

class Nameserver extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * @throws Exception
     */
    public function add(): void
    {
        $this->api->args["NAMESERVER"] = $this->params["nameserver"];
        $this->api->args["IPADDRESS0"] = $this->params["ipaddress"];
        $this->api->call("AddNameserver");
    }

    /**
     * @throws Exception
     */
    public function modify(): void
    {
        $this->api->args["NAMESERVER"] = $this->params["nameserver"];
        $this->api->args["DELIPADDRESS0"] = $this->params["currentipaddress"];
        $this->api->args["ADDIPADDRESS0"] = $this->params["newipaddress"];
        $this->api->call("ModifyNameserver");
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->api->args["NAMESERVER"] = $this->params["nameserver"];
        $this->api->call("DeleteNameserver");
    }
}
