<?php

namespace WHMCS\Module\Registrar\Keysystems\Commands;

use WHMCS\Domains\DomainLookup\SearchResult;

class CheckDomains extends CommandBase
{
    /**
     * @var array<SearchResult>
     */
    private $searchResults = [];

    /**
     * @param array<string, mixed> $params
     * @param array<string> $tlds
     */
    public function __construct(array $params, array $tlds)
    {
        parent::__construct($params);

        if ($params['isIdnDomain'] && !empty($params['punyCodeSearchTerm'])) {
            $searchTerm = strtolower($params['punyCodeSearchTerm']);
        } else {
            $searchTerm = strtolower($params['searchTerm']);
        }

        $i = 0;
        foreach ($tlds as $tld) {
            $this->api->args['DOMAIN' . $i] = $searchTerm . $tld;
            $this->searchResults[$i] = new SearchResult($searchTerm, $tld);
            $i++;
        }
    }

    /**
     * @return SearchResult[]
     */
    public function getResults(): array
    {
        return $this->searchResults;
    }
}
