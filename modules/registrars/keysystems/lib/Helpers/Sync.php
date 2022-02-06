<?php

namespace WHMCS\Module\Registrar\RRPproxy\Helpers;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Module\Registrar\RRPproxy\Commands\QueryDomainList;
use WHMCS\Module\Registrar\RRPproxy\Commands\SetDomainRenewalMode;

class Sync
{
    /**
     * @var array<string, mixed>
     */
    private array $params;
    /**
     * @var int
     */
    private int $expirationWarningTs;
    /**
     * @var array<array<string, mixed>>
     */
    private array $reactivatedDomains;
    /**
     * @var array<array<string, mixed>>
     */
    private array $expiringDomains;
    /**
     * @var array<array<string, mixed>>
     */
    private array $cancelledDomains;
    /**
     * @var array<array<string, mixed>>
     */
    private array $notRenewingDomains;
    /**
     * @var array<string, string>
     */
    private array $failedDomains;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        $this->expirationWarningTs = strtotime("+10 days");
    }

    /**
     * @param string $domain
     * @return array<string, mixed>
     */
    private function getParamsWithDomain(string $domain): array
    {
        $params = $this->params;
        $domainObj = new \WHMCS\Domains\Domain($domain);
        $params["sld"] = $domainObj->getSLD();
        $params["tld"] = $domainObj->getTLD();
        return $params;
    }

    /**
     * @return void
     */
    public function sync(): void
    {
        try {
            $currentTs = time();
            $expiredDomains = $this->getDomainsWithStatus(["Expired", "Grace", "Redemption"]);
            $pendingCancellation = $this->getDomainsWithStatus(["Active"], true);
            $cancelledDomains = $this->getDomainsWithStatus(["Cancelled"]);
            $activeDomains = $this->getDomainsWithStatus(["Active"], false);

            $domainList = new QueryDomainList($this->params);
            $domainList->execute();

            foreach ($domainList->api->propertiesList as $properties) {
                foreach ($properties["DOMAIN"] as $key => $domain) {
                    try {
                        $renewalMode = $properties["RENEWALMODE"][$key];
                        /*
                         * NOTE: PAIDUNTILDATE Is not available in QueryDomainList
                         * Not a huge deal as the Domain Sync process will then fix the expiration date if necessary
                         */
                        $expirationDate = $properties["DOMAINREGISTRATIONEXPIRATIONDATE"][$key];
                        if ($renewalMode == "RENEWONCE") {
                            $expirationTs = strtotime($expirationDate . " +1 year");
                            if ($expirationTs !== false) {
                                $expirationDate = date("Y-m-d H:i:s", $expirationTs);
                            }
                        }
                        $expirationDate = $domainList->api->castDate($expirationDate);

                        $domainData = [
                            "domain" => $domain,
                            "expirationDate" => $expirationDate["long"],
                            "renewalMode" => $renewalMode
                        ];

                        // Check if domain is expired in WHMCS but still active / active again in RRPproxy
                        if (in_array($domain, $expiredDomains) && $expirationDate["ts"] > $currentTs) {
                            $this->updateDomain($domain, ["expirydate" => $expirationDate["long"], "status" => "Active"]);
                            $this->reactivatedDomains[] = $domainData;
                        }

                        // Check if domain is cancelled in WHMCS but still active in RRPproxy
                        if ((in_array($domain, $pendingCancellation) || in_array($domain, $cancelledDomains)) && !in_array($renewalMode, ["AUTOEXPIRE", "AUTODELETE"])) {
                            $params = $this->getParamsWithDomain($domain);
                            $setRenewalMode = new SetDomainRenewalMode($params);
                            $setRenewalMode->setAutoExpire();
                            $setRenewalMode->execute();
                            $this->updateDomain($domain, ["status" => "Cancelled"]);
                            $this->cancelledDomains[] = $domainData;
                        }

                        // Check if any domains with active auto-renew in WHMCS are marked as AUTOEXPIRE / AUTODELETE but are not cancelled in WHMCS
                        if (in_array($domain, $activeDomains) && in_array($renewalMode, ["AUTOEXPIRE", "AUTODELETE"])) {
                            $this->notRenewingDomains[] = $domainData;
                        }

                        // Check if domain is about to expire
                        if (in_array($renewalMode, ["DEFAULT", "AUTOEXPIRE"]) && ($expirationDate["ts"] > $currentTs) && ($expirationDate["ts"] <= $this->expirationWarningTs)) {
                            $this->expiringDomains[] = $domainData;
                        }
                    } catch (Exception $ex) {
                        $this->failedDomains[$domain] = $ex->getMessage();
                    }
                }
            }
        } catch (Exception $ex) {
            echo "ERROR: failed to get domain list! {$ex->getMessage()}\n";
        }
    }

    /**
     * @param array<string> $statusValues Available statuses: 'Pending', 'Pending Registration', 'Pending Transfer',
     * 'Active', 'Grace', 'Redemption', 'Expired','Cancelled', 'Fraud', 'Transferred Away'
     * @param ?bool $doNotRenew
     * @return array<string>
     */
    private function getDomainsWithStatus(array $statusValues, ?bool $doNotRenew = null): array
    {
        $query = DB::table("tbldomains")
            ->whereIn("status", $statusValues)
            ->where("registrar", "=", "keysystems");
        if ($doNotRenew !== null) {
            $query->where("donotrenew", "=", $doNotRenew);
        }
        return $query->pluck("domain")->toArray();
    }

    /**
     * @param string $domain
     * @param array<string, mixed> $updateArray
     * @return void
     */
    private function updateDomain(string $domain, array $updateArray): void
    {
        DB::table("tbldomains")
            ->where("domain", "=", $domain)
            ->update($updateArray);
    }

    /**
     * @return bool
     */
    public function sendReport(): bool
    {
        $html = "";

        if (!empty($this->reactivatedDomains)) {
            $html .= "The following domains were expired in WHMCS but their expiration date at RRPproxy actually is in the future, so they have been reactivated:";
            $html .= "<table><tr><th>Domain</th><th>Expiration date</th></tr>";
            foreach ($this->reactivatedDomains as $reactivatedDomain) {
                $html .= "<tr><td>{$reactivatedDomain["domain"]}</td><td>{$reactivatedDomain["expirationDate"]}</td></tr>";
            }
            $html .= "</tr></table><hr />";
        }

        if (!empty($this->cancelledDomains)) {
            $html .= "The following domains were cancelled in WHMCS and thus have been marked for expiration at RRPproxy:";
            $html .= "<table><tr><th>Domain</th><th>Expiration date</th></tr>";
            foreach ($this->cancelledDomains as $cancelledDomain) {
                $html .= "<tr><td>{$cancelledDomain["domain"]}</td><td>{$cancelledDomain["expirationDate"]}</td></tr>";
            }
            $html .= "</tr></table><hr />";
        }

        if (!empty($this->notRenewingDomains)) {
            $html .= "The following domains are active in WHMCS but marked for expiration or deletion in RRPproxy:";
            $html .= "<table><tr><th>Domain</th><th>Renewal mode</th></th><th>Expiration date</th></tr>";
            foreach ($this->notRenewingDomains as $notRenewingDomain) {
                $html .= "<tr><td>{$notRenewingDomain["domain"]}</td><td>{$notRenewingDomain["renewalMode"]}</td><td>{$notRenewingDomain["expirationDate"]}</td></tr>";
            }
            $html .= "</tr></table><hr />";
        }

        if (!empty($this->expiringDomains)) {
            $html .= "The following domains are expiring within the next 10 days:";
            $html .= "<table><tr><th>Domain</th><th>Renewal mode</th></th><th>Expiration date</th></tr>";
            foreach ($this->expiringDomains as $expiringDomain) {
                $html .= "<tr><td>{$expiringDomain["domain"]}</td><td>{$expiringDomain["renewalMode"]}</td><td>{$expiringDomain["expirationDate"]}</td></tr>";
            }
            $html .= "</tr></table><hr />";
        }

        if (!empty($this->failedDomains)) {
            $html .= "The following domains have failed the checks:";
            $html .= "<table><tr><th>Domain</th><th>Reason</th></tr>";
            foreach ($this->failedDomains as $failedDomain => $reason) {
                $html .= "<tr><td>$failedDomain</td><td>$reason</li></td></tr>";
            }
            $html .= "</tr></table><hr />";
        }

        if (!$html) {
            return false;
        }

        $command = "sendadminemail";
        $values["customsubject"] = "RRPproxy Daily Cron";
        $values["custommessage"] = $html;
        $values["type"] = "system";
        $values["mergefields"] = [];
        $values["deptid"] = 0;

        localAPI($command, $values);
        return true;
    }
}
