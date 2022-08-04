<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

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

    public function setRenewOnce(): SetDomainRenewalMode
    {
        $this->api->args["RENEWALMODE"] = "RENEWONCE";
        return $this;
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
