<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;
use ReflectionClass;
use WHMCS\Module\Registrar\RRPproxy\APIClient;

abstract class CommandBase
{
    public APIClient $api;
    private string $commandName;
    public string $domainName;
    /**
     * @var array<string, mixed>
     */
    public array $params;
    private bool $success;
    /**
     * @var array<string>
     */
    private array $errors = [];

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        $this->api = new APIClient($params);
        $this->params = $params;
        $this->commandName = (new ReflectionClass($this))->getShortName();
        if (isset($params["sld"]) && isset($params["tld"])) {
            $this->domainName = $params["sld"] . "." . $params["tld"];
        }
    }

    /**
     * @param string $commandName
     * @return void
     */
    public function setCommandName(string $commandName): void
    {
        $this->commandName = $commandName;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute(): void
    {
        try {
            $this->api->call($this->commandName);
            $this->setSuccess();
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function executeGetAllPages(): void
    {
        try {
            $this->api->call($this->commandName, true);
            $this->setSuccess();
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * @return string
     */
    public function getErrors(): string
    {
        return implode(", ", $this->errors);
    }

    /**
     * @return void
     */
    public function setSuccess(): void
    {
        $this->success = true;
    }

    /**
     * @param string $error
     * @return void
     */
    public function setError(string $error): void
    {
        $this->success = false;
        $this->errors[] = $error;
    }
}
