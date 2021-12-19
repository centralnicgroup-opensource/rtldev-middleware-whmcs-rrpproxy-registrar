<?php

namespace WHMCS\Module\Registrar\RRPproxy\Features;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Commands\CommandBase;
use WHMCS\Module\Registrar\RRPproxy\Commands\ModifyDomain;

class DNSZone extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DNSZONE"] = $this->domainName;
    }

    /**
     * @throws Exception
     */
    private function getStatus(): void
    {
        $this->api->call("CheckDNSZone");
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function exists(): bool
    {
        $this->getStatus();
        return $this->api->response["CODE"] != 210;
    }

    /**
     * @return array<array<string, mixed>>
     * @throws Exception
     */
    public function get(): array
    {
        if (!$this->exists()) {
            $this->init();
        }
        $this->api->args["ORDERBY"] = "type";
        $this->api->args["WIDE"] = 1;
        $this->api->call("QueryDNSZoneRRList");

        $web = new WebFwd($this->params);
        $values = [];
        for ($i = 0; $i < $this->api->properties['COUNT'][0]; $i++) {
            $name = $this->api->properties['NAME'][$i];
            $type = $this->api->properties['TYPE'][$i];
            $content = $this->api->properties['CONTENT'][$i];
            $priority = $this->api->properties['PRIO'][$i];
            $ttl = $this->api->properties['TTL'][$i];

            if ($this->api->properties['LOCKED'][$i] == 1) {
                $fwd = $web->get($name);
                if ($fwd) {
                    $values[] = $fwd;
                }
                continue;
            }
            if ($type == 'MX') {
                if ($content == $priority) {
                    continue;
                }
                if (substr($content, 0, strlen($priority)) === $priority) {
                    $content = substr($content, strlen($priority) + 1);
                }
            }

            $values[] = [
                'hostname' => $name,
                'type' => $type,
                'address' => $content,
                'priority' => $priority,
                'ttl' => $ttl
            ];
        }

        return $values;
    }

    /**
     * @throws Exception
     */
    private function init(): void
    {
        $this->api->call("AddDNSZone");

        $params = $this->params;
        $params["ns1"] = "ns1.dnsres.net";
        $params["ns2"] = "ns2.dnsres.net";
        $params["ns3"] = "ns3.dnsres.net";
        $params["ns4"] = "";
        $params["ns5"] = "";
        $domain = new ModifyDomain($params);
        $domain->setNameServers();
        $domain->execute();
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $fwd = new WebFwd($this->params);

        // Determine records to delete
        $i = 0;
        $records = $this->get();
        $this->api->args = ["DNSZONE" => $this->domainName];
        foreach ($records as $record) {
            if (in_array($record["type"], ["URL", "FRAME"])) {
                $fwd->del($record["hostname"]);
                continue;
            }
            $values = [
                $record["hostname"],
                $record["ttl"],
                'IN',
                $record["type"],
                $record["address"]
            ];
            if ($record["type"] == 'NS') {
                unset($values[2]);
            }
            $this->api->args["DELRR" . $i++] = implode(' ', $values);
        }

        // Determine records to add
        $zone = [];
        $ttl = is_numeric($this->params['DefaultTTL']) ? $this->params['DefaultTTL'] : 28800;
        foreach ($this->params['dnsrecords'] as $record) {
            if (!$record['address']) {
                continue;
            }
            if (!$record['hostname'] || $record['hostname'] == $this->domainName) {
                $record['hostname'] = "@";
            }

            switch ($record['type']) {
                case "URL":
                case "FRAME":
                    $fwd->add($record['hostname'], $record['address'], $record['type'] == "URL");
                    break;
                case "MX":
                case "SRV":
                    $zone[] = sprintf("%s %s IN %s %s %s", $record['hostname'], $ttl, $record['type'], $record['priority'], $record['address']);
                    break;
                case "NS":
                    $zone[] = sprintf("%s %s %s %s", $record['hostname'], $ttl, $record['type'], $record['address']);
                    break;
                default:
                    $zone[] = sprintf("%s %s IN %s %s", $record['hostname'], $ttl, $record['type'], $record['address']);
            }
        }
        $i = 0;
        foreach ($zone as $record) {
            $this->api->args["ADDRR" . $i++] = $record;
        }

        $this->api->call("ModifyDNSZone");
    }
}
