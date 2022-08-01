<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

class QueryExchangeRates extends CommandBase
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params, string $fromCurrency, string $toCurrency)
    {
        parent::__construct($params);

        $this->api->args["CURRENCYFROM"] = $fromCurrency;
        $this->api->args["CURRENCYTO"] = $toCurrency;
        $this->api->args["LIMIT"] = 1;
    }
}
