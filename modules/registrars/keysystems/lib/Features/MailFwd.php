<?php

namespace WHMCS\Module\Registrar\RRPproxy\Features;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Commands\CommandBase;

class MailFwd extends CommandBase
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $values = [];
    /**
     * @var array<string>
     */
    private array $orig = [];
    /**
     * @var array<string>
     */
    private array $add = [];
    /**
     * @var array<string>
     */
    private array $del = [];

    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args = ["dnszone" => $this->domainName];
        $this->api->call("QueryMailFwdList");

        for ($i = 0; $i < $this->api->properties['TOTAL'][0]; $i++) {
            $this->orig[$this->api->properties['FROM'][$i]] = $this->api->properties['TO'][$i];
            $from = explode("@", $this->api->properties['FROM'][$i]);
            $this->values[$i] = [
                'prefix' => $from[0],
                'forwardto' => $this->api->properties['TO'][$i]
            ];
        }
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $this->determineRecords();
        $this->deleteForwards();
        $this->addForwards();
        if (!empty($this->getErrors())) {
            throw new Exception($this->getErrors());
        }
    }

    /**
     * @return void
     */
    private function determineRecords(): void
    {
        foreach ($this->params["prefix"] as $key => $value) {
            $from = $value . "@" . $this->domainName;
            $to = $this->params["forwardto"][$key];
            if (!$value || !$to) {
                // invalid
                continue;
            }
            if (isset($this->orig[$from])) {
                // already present
                if ($this->orig[$from] == $to) {
                    // no change needed
                    continue;
                } else {
                    // differs, needs to be deleted and readded
                    $this->del[$from] = $this->orig[$from];
                    $this->add[$from] = $to;
                }
            } else {
                // not present, needs to be added
                $this->add[$from] = $to;
            }
        }

        foreach ($this->orig as $from => $to) {
            $explode = explode("@", $from);
            if (!in_array($explode[0], $this->params["prefix"])) {
                // not present locally anymore, needs to be deleted
                $this->del[$from] = $to;
            }
        }
    }

    /**
     * @return void
     */
    private function deleteForwards(): void
    {
        foreach ($this->del as $from => $to) {
            try {
                $this->api->args = ["from" => $from, "to" => $to];
                $this->api->call("DeleteMailFwd");
            } catch (Exception $e) {
                $this->setError($e->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    private function addForwards(): void
    {
        foreach ($this->add as $from => $to) {
            try {
                $this->api->args = ["from" => $from, "to" => $to];
                $this->api->call("AddMailFwd");
            } catch (Exception $e) {
                $this->setError($e->getMessage());
            }
        }
    }
}
