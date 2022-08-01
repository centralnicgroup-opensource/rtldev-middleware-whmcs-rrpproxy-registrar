<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

class GetZoneInfo extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     * @param string $tld
     */
    public function __construct(array $params, string $tld)
    {
        parent::__construct($params);

        $this->api->args["ZONE"] = $tld;
    }
}
