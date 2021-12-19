<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

class TradeDomain extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params, string $contactHandle)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;
        $this->api->args["OWNERCONTACT0"] = $contactHandle;
        if ($params['tld'] == "swiss") {
            $this->api->args['X-SWISS-UID'] = $params['additionalfields']['UID'];
        }
    }
}
