<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

class PushDomain extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;
        if (!empty($params['transfertag'])) {
            $this->api->args["TARGET"] = $params['transfertag'];
        }
    }
}
