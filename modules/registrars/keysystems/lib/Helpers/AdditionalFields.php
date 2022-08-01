<?php

namespace WHMCS\Module\Registrar\Keysystems\Helpers;

class AdditionalFields
{
    /**
     * @var array<string, mixed>
     */
    public array $fields;
    public bool $isDomainApplication;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $tld = $params["domainObj"]->getLastTLDSegment();
        $extensions = [];
        $domainApplication = false;
        $extensions_path = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "..", "tlds", $tld . ".php"]));
        if ($extensions_path !== false && file_exists($extensions_path)) {
            include $extensions_path;
        }
        $this->fields = $extensions;
        $this->isDomainApplication = $domainApplication;
    }
}
