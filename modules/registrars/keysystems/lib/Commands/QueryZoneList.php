<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

class QueryZoneList extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }
}
