<?php

namespace WHMCS\Module\Registrar\Keysystems\Helpers;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use WHMCS\Module\Registrar\Keysystems\Commands\QueryDNSZoneList;
use WHMCS\Module\Registrar\Keysystems\Commands\QueryDomainList;
use WHMCS\Module\Registrar\Keysystems\Commands\SetDomainRenewalMode;

class Sync
{
    /**
     * @var array<string, mixed>
     */
    private $params;
    /**
     * @var int
     */
    private $expirationWarningTs;
    /**
     * @var array<array<string, string>>
     */
    private $reactivatedDomains;
    /**
     * @var array<array<string, string>>
     */
    private $expiringDomains;
    /**
     * @var array<array<string, string>>
     */
    private $cancelledDomains;
    /**
     * @var array<array<string, string>>
     */
    private $notRenewingDomains;
    /**
     * @var array<array<string, string>>
     */
    private $failedDomains;
    /**
     * @var array<array<string, string>>
     */
    private $orphanDomains;
    /**
     * @var array<array<string, string>>
     */
    private $orphanDnsZones;

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
        $dnsZones = [];
        try {
            $dnsList = new QueryDNSZoneList($this->params);
            $dnsList->execute();
            foreach ($dnsList->api->propertiesList as $properties) {
                foreach ($properties["DNSZONE"] as $domain) {
                    $dnsZones[] = $domain;
                }
            }
        } catch (Exception $ex) {
            // We ignore errors - perhaps KeyDNS not activated
        }

        try {
            $currentTs = time();
            $expiredDomains = $this->getDomainsWithStatus(["Expired", "Grace", "Redemption"]);
            $pendingCancellation = $this->getDomainsWithStatus(["Active"], true);
            $cancelledDomains = $this->getDomainsWithStatus(["Cancelled"]);
            $activeDomains = $this->getDomainsWithStatus(["Active"], false);

            $domainList = new QueryDomainList($this->params);
            $domainList->execute();

            $domains = [];
            foreach ($domainList->api->propertiesList as $properties) {
                foreach ($properties["DOMAINIDN"] as $key => $domain) {
                    $domains[] = $domain;
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

                        // Check if domain is expired in WHMCS but still active / active again in CentralNic Reseller/RRPproxy
                        if (in_array($domain, $expiredDomains) && $expirationDate["ts"] > $currentTs) {
                            $this->updateDomain($domain, ["expirydate" => $expirationDate["long"], "status" => "Active"]);
                            $this->reactivatedDomains[] = $domainData;
                        }

                        // Check if domain is cancelled in WHMCS but still active in CentralNic Reseller/RRPproxy
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
                        $this->failedDomains[] = ["domain" => $domain, "reason" => $ex->getMessage()];
                    }
                }
            }

            $orphanDomains = array_diff(array_merge($activeDomains, $pendingCancellation), $domains);
            foreach ($orphanDomains as $orphanDomain) {
                $this->orphanDomains[] = ["domain" => $orphanDomain];
            }

            $orphanZones = array_diff($dnsZones, $domains);
            foreach ($orphanZones as $orphanZone) {
                $this->orphanDnsZones[] = ["zone" => $orphanZone];
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
            $html .= $this->renderTable(
                "Domains in expired/grace/redemption status but active at CentralNic Reseller - Reactivated!",
                $this->reactivatedDomains,
                ["domain", "expirationDate"]
            );
        }

        if (!empty($this->cancelledDomains)) {
            $html .= $this->renderTable(
                "Cancelled domains - Marked for automatic expiration at CentralNic Reseller!",
                $this->cancelledDomains,
                ["domain", "expirationDate"]
            );
        }

        if (!empty($this->notRenewingDomains)) {
            $html .= $this->renderTable(
                "Domains active in WHMCS but marked for expiration or deletion at CentralNic Reseller",
                $this->notRenewingDomains,
                ["domain", "renewalMode", "expirationDate"]
            );
        }

        if (!empty($this->orphanDomains)) {
            $html .= $this->renderTable(
                "Domains active in WHMCS but not existing at CentralNic Reseller",
                $this->orphanDomains,
                ["domain"]
            );
        }

        if (!empty($this->orphanDnsZones)) {
            $html .= $this->renderTable(
                "DNS Zones for not existing domains",
                $this->orphanDnsZones,
                ["zone"]
            );
        }

        if (!empty($this->expiringDomains)) {
            $html .= $this->renderTable(
                "Domains are expiring within the next 10 days",
                $this->expiringDomains,
                ["domain", "renewalMode", "expirationDate"]
            );
        }

        if (!empty($this->failedDomains)) {
            $html .= $this->renderTable(
                "The following domains have failed the consistency checks",
                $this->failedDomains,
                ["domain", "reason"]
            );
        }

        if (!$html) {
            return false;
        }

        $command = "sendadminemail";
        $values["customsubject"] = "CentralNic Reseller Daily Cron";
        $values["custommessage"] = $html;
        $values["type"] = "system";
        $values["mergefields"] = [];
        $values["deptid"] = 0;

        localAPI($command, $values);
        return true;
    }

    /**
     * @param string $description
     * @param array<array<string, mixed>> $data
     * @param array<string> $keys
     * @return string
     */
    private function renderTable(string $description, array $data, array $keys): string
    {
        $html = $description . ":";
        $html .= "<table><tr>";
        foreach ($keys as $header) {
            $words = preg_split('/(?=[A-Z])/', $header);
            if ($words !== false) {
                $header = ucwords(implode(' ', $words));
            }
            $html .= "<th>$header</th>";
        }
        $html .= "</tr>";
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($keys as $key) {
                $html .= "<td>$row[$key]</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table><hr />";
        return $html;
    }
}
