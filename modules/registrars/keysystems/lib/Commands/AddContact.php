<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

class AddContact extends CommandBase
{
    private $handle;

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $contact
     */
    public function __construct(array $params, array $contact)
    {
        parent::__construct($params);

        $this->api->args = $contact;
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        parent::execute();
        $this->handle = $this->api->properties["CONTACT"][0];
    }

    public function getContactHandle(): string
    {
        return $this->handle;
    }
}
