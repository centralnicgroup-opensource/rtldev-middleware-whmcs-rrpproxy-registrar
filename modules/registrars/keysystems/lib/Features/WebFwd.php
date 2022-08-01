<?php

namespace WHMCS\Module\Registrar\Keysystems\Features;

use Exception;
use WHMCS\Module\Registrar\Keysystems\Commands\CommandBase;

class WebFwd extends CommandBase
{
    /**
     * @var array<string>
     */
    public $names = [];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * @param string $name
     * @return array<string, mixed>
     * @throws Exception
     */
    public function get(string $name): array
    {
        $this->setSource($name);
        $this->api->args["wide"] = 1;
        $this->api->call("QueryWebFwdList");
        if ($this->api->properties['TOTAL'][0] > 0 && !in_array($name, $this->names)) {
            $this->names[] = $name;
            return [
                'hostname' => $name,
                'type' => $this->api->properties['TYPE'][0] == "rd" ? "URL" : "FRAME",
                'address' => $this->api->properties['TARGET'][0]
            ];
        }
        return [];
    }

    /**
     * @param string $hostName
     * @param string $address
     * @param bool $isUrl
     * @throws Exception
     */
    public function add(string $hostName, string $address, bool $isUrl): void
    {
        $this->setSource($hostName);
        $this->api->args["target"] = $address;
        $this->api->args["type"] = $isUrl ? "RD" : "MRD";
        $this->api->call("AddWebFwd");
    }

    /**
     * @param string $hostName
     * @throws Exception
     */
    public function del(string $hostName): void
    {
        $this->setSource($hostName);
        $this->api->call("DeleteWebFwd");
    }

    /**
     * @param string $hostName
     * @return void
     */
    private function setSource(string $hostName): void
    {
        $source = ($hostName == "@") ? $this->domainName : $hostName . "." . $this->domainName;
        $this->api->args["source"] = $source;
    }
}
