<?php

namespace WHMCS\Module\Registrar\Keysystems\Widgets;

class Currency
{
    /** @var array<string, mixed> */
    private $data = [];

    public function __construct()
    {
        // init currency
        $currencies = localAPI("GetCurrencies", []);
        $currenciesAsAssocList = [];
        if ($currencies["result"] === "success") {
            foreach ($currencies["currencies"]["currency"] as $idx => $d) {
                $currenciesAsAssocList[$d["code"]] = $d;
            }
        }
        $this->data["currencies"] = $currenciesAsAssocList;
    }

    /**
     * get currency id of a currency identified by given currency code
     * @param string $currency currency code
     * @return null|int currency id or null if currency is not configured
     */
    public function getId(string $currency): ?int
    {
        return (isset($this->data["currencies"][$currency])) ?
            $this->data["currencies"][$currency]["id"] :
            null;
    }
}
