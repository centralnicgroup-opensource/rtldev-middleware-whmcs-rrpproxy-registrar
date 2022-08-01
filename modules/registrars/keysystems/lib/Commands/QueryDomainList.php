<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

/**
 * @see https://wiki.rrpproxy.net/api/api-command/QueryDomainList
 */
class QueryDomainList extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["WIDE"] = 1;
        $this->api->args["ORDER"] = "ASC";
        $this->api->args["ORDERBY"] = "DOMAIN";
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
    public function setLimit(int $limit = 1000): QueryDomainList
    {
        $this->api->args["LIMIT"] = $limit;
        return $this;
    }
}
