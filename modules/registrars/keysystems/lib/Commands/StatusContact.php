<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;

class StatusContact extends CommandBase
{
    public string $contactHandle;
    public string $email;
    public bool $verificationRequested;
    public bool $verified;

    /**
     * @param array<string, mixed> $params
     * @param string $contactHandle
     * @throws Exception
     */
    public function __construct(array $params, string $contactHandle)
    {
        parent::__construct($params);

        $this->contactHandle = $contactHandle;
        $this->api->args["CONTACT"] = $this->contactHandle;

        $this->execute();

        $this->email = $this->api->properties["EMAIL"][0];
        $this->verificationRequested = ($this->api->properties["VERIFICATIONREQUESTED"][0] == 1);
        $this->verified = ($this->api->properties["VERIFIED"][0] == 1);
    }
}
