<?php

namespace WHMCS\Module\Registrar\RRPproxy;

use CNIC\ClientFactory;
use WHMCS\Domain\Registrar\Domain;
use Exception;

class APIClient
{
    public string $command;
    /**
     * @var array<string, mixed>
     */
    public array $params;
    /**
     * @var array<string, mixed>
     */
    public array $response;
    /**
     * @var array<string, mixed>
     */
    public array $properties;
    /**
     * @var Domain
     */
    public Domain $domain;
    /**
     * @var array<string, mixed>
     */
    public array $args = [];

    /**
     * @param array<string, mixed> $params
     * @param string|null $domain
     */
    public function __construct(array $params = [], string $domain = null)
    {
        if ($params) {
            $this->params = $params;
        } else {
            $this->params = \getregistrarconfigoptions("rrpproxy");
        }
        if (!$domain && isset($params["sld"]) && $params["tld"]) {
            $domain = $params["sld"] . "." . $params["tld"];
        }
        if ($domain) {
            $this->domain = new Domain($this, $domain);
        }
    }

    /**
     * Make an API request using the provided command and return response in Hash Format
     * @param string $command API command to request
     * @throws Exception
     */
    public function call(string $command): void
    {
        $this->command = $command;
        $cl = ClientFactory::getClient([
            "registrar" => "RRPproxy"
        ]);
        if ($this->params["TestMode"]) {
            $cl->useOTESystem();
            $cl->setCredentials($this->params["Username"], html_entity_decode($this->params["TestPassword"], ENT_QUOTES));
        } else {
            $cl->setCredentials($this->params["Username"], html_entity_decode($this->params["Password"], ENT_QUOTES));
        }

        $cl->setReferer($GLOBALS["CONFIG"]["SystemURL"])
            ->setUserAgent("WHMCS", $GLOBALS["CONFIG"]["Version"], ["keysystems" => RRPPROXY_VERSION]);

        if (\WHMCS\Config\Setting::getValue("ModuleDebugMode")) {
            $cl->setCustomLogger(new Logger())
                ->enableDebugMode();
        }

        if (strlen($this->params["ProxyServer"])) {
            $cl->setProxy($this->params["ProxyServer"]);
        }

        $response = $cl->request(array_merge(["command" => $command], $this->args));
        $this->response = $response->getHash();

        if (!preg_match('/^2/', $this->response["CODE"])) {
            throw new Exception($this->response["DESCRIPTION"]);
        }

        $this->properties = $this->response["PROPERTY"] ?? [];
    }
}
