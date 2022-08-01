<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

class ModifyContact extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $contact
     */
    public function __construct(array $params, array $contact)
    {
        parent::__construct($params);

        $this->api->args = $contact;
    }
}
