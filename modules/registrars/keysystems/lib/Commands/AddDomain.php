<?php

namespace WHMCS\Module\Registrar\RRPproxy\Commands;

use Exception;
use WHMCS\Module\Registrar\RRPproxy\Features\Contact;
use WHMCS\Module\Registrar\RRPproxy\Helpers\AdditionalFields;

class AddDomain extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->api->args["DOMAIN"] = $this->domainName;
        $this->api->args["PERIOD"] = $params['regperiod'];
        $this->api->args["TRANSFERLOCK"] = (int) $params["TransferLock"] == "on";

        // Create contacts if not existing and get contact handle
        if ($params['tld'] == 'it' && $params['additionalfields']['Legal Type'] == 'Italian and foreign natural persons') {
            $params['companyname'] = '';
        }
        $contactId = Contact::getOrCreateOwnerContact($params, $params);
        $adminContactId = Contact::getOrCreateAdminContact($params, $params);
        $this->api->args["OWNERCONTACT0"] = $contactId;
        $this->api->args["ADMINCONTACT0"] = $adminContactId;
        $this->api->args["TECHCONTACT0"] = $adminContactId;
        $this->api->args["BILLINGCONTACT0"] = $adminContactId;

        // Handle nameservers
        for ($i = 1; $i <= 5; $i++) {
            if (empty($params["ns$i"])) {
                break;
            }
            $this->api->args["NAMESERVER" . ($i - 1)] = $params["ns$i"];
        }

        if ($params['idprotection'] && !$this->api->params['TestMode']) {
            $this->api->args['X-WHOISPRIVACY'] = 1;
        }
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $fields = new AdditionalFields($this->params);
        $this->api->args = array_merge($this->api->args, $fields->fields);
        if ($fields->isDomainApplication) {
            $this->setCommandName("AddDomainApplication");
        }
        parent::execute();
    }
}
