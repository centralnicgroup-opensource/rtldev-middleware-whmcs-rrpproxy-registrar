<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

class GetNameSuggestion extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["NAME"] = $params['searchTerm'];
        $this->api->args["SHOW-UNAVAILABLE"] = 0;
    }
}
