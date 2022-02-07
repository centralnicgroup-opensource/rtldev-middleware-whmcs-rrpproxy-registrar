<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

/**
 * @see https://wiki.rrpproxy.net/api/api-command/QueryDNSZoneList
 */
class QueryDNSZoneList extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["ORDER"] = "ASC";
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute(): void
    {
        parent::executeGetAllPages();
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit = 1000): QueryDNSZoneList
    {
        $this->api->args["LIMIT"] = $limit;
        return $this;
    }
}
