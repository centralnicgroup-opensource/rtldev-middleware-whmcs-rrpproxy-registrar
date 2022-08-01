<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

class SetAuthCode extends CommandBase
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
     * @return string
     * @throws Exception
     */
    public function getAuthCode(): string
    {
        $this->execute();
        return htmlspecialchars($this->api->properties["AUTH"][0]);
    }
}
