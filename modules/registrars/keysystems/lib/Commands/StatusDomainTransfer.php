<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use Exception;

class StatusDomainTransfer extends CommandBase
{
    private string $status;
    /**
     * @var array<string>
     */
    private array $transferLog;

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;

        $this->execute();
        $this->status = strtolower($this->api->properties["TRANSFERSTATUS"][0]);
        $this->transferLog = $this->api->properties["TRANSFERLOG"];
    }

    /**
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->status == "failed";
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return implode("\n", $this->transferLog);
    }
}
