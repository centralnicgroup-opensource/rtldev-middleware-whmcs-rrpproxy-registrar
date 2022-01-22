<?php

namespace WHMCS\Module\Registrar\RRPproxy\Helpers;

class AdditionalFields
{
    /**
     * @var array<string, mixed>
     */
    public array $fields;
    public bool $isDomainApplication;

    public function __construct(string $tld)
    {
        $extensions = [];
        $domainApplication = false;
        $extensions_path = implode(DIRECTORY_SEPARATOR, [__DIR__, "tlds", $tld . ".php"]);
        if (file_exists($extensions_path)) {
            include $extensions_path;
        }
        $this->fields = $extensions;
        $this->isDomainApplication = $domainApplication;
    }
}
