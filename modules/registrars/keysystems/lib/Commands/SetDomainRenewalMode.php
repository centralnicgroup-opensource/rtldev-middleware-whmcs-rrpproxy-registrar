<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

class SetDomainRenewalMode extends CommandBase
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
     * @return $this
     */
    public function setAutoDelete(): SetDomainRenewalMode
    {
        $this->api->args["RENEWALMODE"] = "AUTODELETE";
        return $this;
    }

    /**
     * @return $this
     */
    public function setAutoExpire(): SetDomainRenewalMode
    {
        $this->api->args["RENEWALMODE"] = "AUTOEXPIRE";
        return $this;
    }
}
