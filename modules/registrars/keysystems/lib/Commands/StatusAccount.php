<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

class StatusAccount extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->execute();
    }
}
