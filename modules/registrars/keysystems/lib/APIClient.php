<?php

namespace WHMCS\Module\Registrar\RRPproxy;

use CNIC\ClientFactory;
use CNIC\HEXONET\SessionClient;
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
     * @var SessionClient
     */
    public SessionClient $client;
    /**
     * @var array<string, mixed>
     */
    public array $response = [];
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $responseList = [];
    /**
     * @var array<string, mixed>
     */
    public array $properties;
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $propertiesList;
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
     * @throws Exception
     */
    public function __construct(array $params = [], string $domain = null)
    {
        if (!function_exists("getregistrarconfigoptions")) {
            include implode(DIRECTORY_SEPARATOR, [ROOTDIR, "includes", "registrarfunctions.php"]);
        }
        if ($params) {
            $this->params = $params;
        } else {
            $this->params = \getregistrarconfigoptions("keysystems");
        }
        if (!$domain && isset($params["sld"]) && $params["tld"]) {
            $domain = $params["sld"] . "." . $params["tld"];
        }
        if ($domain) {
            $this->domain = new Domain($this, $domain);
        }
        $this->client = $this->getClient();
    }

    /***
     * @return SessionClient
     * @throws Exception
     */
    public function getClient(): SessionClient
    {
        $client = ClientFactory::getClient([
            "registrar" => "RRPproxy"
        ]);
        if ($this->params["TestMode"]) {
            $client->useOTESystem();
            $client->setCredentials($this->params["Username"], html_entity_decode($this->params["TestPassword"], ENT_QUOTES));
        } else {
            $client->setCredentials($this->params["Username"], html_entity_decode($this->params["Password"], ENT_QUOTES));
        }

        $client->setReferer($GLOBALS["CONFIG"]["SystemURL"])
            ->setUserAgent("WHMCS", $GLOBALS["CONFIG"]["Version"], ["keysystems" => RRPPROXY_VERSION]);

        if (\WHMCS\Config\Setting::getValue("ModuleDebugMode")) {
            $client->setCustomLogger(new Logger())
                ->enableDebugMode();
        }

        if (strlen($this->params["ProxyServer"])) {
            $client->setProxy($this->params["ProxyServer"]);
        }
        return $client;
    }

    /**
     * Make an API request using the provided command and return response in Hash Format
     * @param string $command API command to request
     * @param bool $getAllPages Use with caution and only with List commands
     * @throws Exception
     */
    public function call(string $command, bool $getAllPages = false): void
    {
        $this->command = $command;

        $requestArgs = array_merge(["command" => $command], $this->args);
        if ($getAllPages) {
            $responses = $this->client->requestAllResponsePages($requestArgs);
        } else {
            $responses = [$this->client->request($requestArgs)];
        }

        foreach ($responses as $response) {
            $responseHashed = $response->getHash();
            if (!preg_match('/^2/', $responseHashed["CODE"])) {
                throw new Exception($responseHashed["DESCRIPTION"]);
            }
            $this->responseList[] = $responseHashed;
            $this->propertiesList[] = $responseHashed["PROPERTY"] ?? [];
        }
        $this->response = end($this->responseList) ?: $this->responseList[0];
        $this->properties = end($this->propertiesList) ?: $this->propertiesList[0];
    }

    /**
     * Cast our UTC API timestamps to local timestamp string and unix timestamp
     * @param string $date API timestamp (YYYY-MM-DD HH:ii:ss)
     * @return array<string, mixed>
     */
    public function castDate(string $date): array
    {
        $utcDate = str_replace(" ", "T", $date) . "Z"; //RFC 3339 / ISO 8601
        $ts = strtotime($utcDate);
        return [
            "ts" => $ts,
            "short" => date("Y-m-d", $ts === false ? 0 : $ts),
            "long" => date("Y-m-d H:i:s", $ts === false ? 0 : $ts)
        ];
    }
}
