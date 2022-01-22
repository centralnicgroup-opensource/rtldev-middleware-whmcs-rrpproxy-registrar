<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class SetAuthCode extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
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
