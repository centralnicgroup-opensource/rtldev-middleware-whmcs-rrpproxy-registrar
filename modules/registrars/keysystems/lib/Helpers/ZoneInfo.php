<?php

namespace WHMCS\Module\Registrar\Keysystems\Helpers;

use DateTime;
use Exception;
use WHMCS\Module\Registrar\Keysystems\Commands\GetZoneInfo;
use WHMCS\Module\Registrar\Keysystems\Models\ZoneModel;

class ZoneInfo
{
    public static function initDb(): void
    {
        ZoneModel::createTableIfNotExists();
    }

    /**
     * @param array<string, mixed> $params
     * @param string|null $tld
     * @return ZoneModel
     * @throws Exception
     */
    public static function get(array $params, ?string $tld = null): ZoneModel
    {
        if (!$tld) {
            $tld = $params["tld"];
        }
        $maxDays = 30;
        $maxUpdates = 100;
        $updates = ZoneModel::query()
            ->where('updated_at', '>', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->count();
        $zone = ZoneModel::query()
            ->where('zone', '=', $tld)
            ->first();

        $updateNeeded = false;
        if ($zone instanceof ZoneModel) {
            $curDate = new DateTime();
            try {
                $zoneDate = new DateTime($zone->updated_at);
                $dateDiff = $zoneDate->diff($curDate);
                if ($dateDiff->format('%r%a') > $maxDays) {
                    $updateNeeded = true;
                }
            } catch (Exception $ex) {
                $updateNeeded = true;
            }
        }

        // $zone = null;
        if (!$zone || ($updateNeeded && $updates < $maxUpdates)) {
            try {
                $zoneInfo = new GetZoneInfo($params, $tld);
                $zoneInfo->execute();
            } catch (Exception $ex) {
                if ($zone instanceof ZoneModel) {
                    return $zone;
                }
                throw new Exception($ex->getMessage());
            }

            /*$p1 = explode(",",$zoneInfo->api->properties['REGISTRATIONPERIODS'][0]);
            $p2 = explode(",",$zoneInfo->api->properties['RENEWALPERIODS'][0]);
            $dbgRow = [
                "registration" => $p1,
                "renewal" => $p2,
                "unsupportedRenewalTerms" => array_values(array_diff($p1, $p2)), // all elements of p1 not in p2
                "missingRenewalTerms" => array_values(array_diff($p2, $p1)) // all elements of p2 not in p1
            ];
            $flag1 = empty($dbgRow["unsupportedRenewalTerms"]);
            $flag2 = empty($dbgRow["missingRenewalTerms"]);
            if (
                (!(
                    !$flag1
                    // 10y reg only -> whmcs keeps 10y for renewal otherwise not
                    && count($dbgRow["registration"])>1
                    && count($dbgRow["unsupportedRenewalTerms"]) === 1
                    && in_array(10, $dbgRow["unsupportedRenewalTerms"])
                ) && !$flag1)
                || !$flag2
            ) {
                //$debugPeriodIssues[$tld] = $dbgRow;
                logActivity(json_encode([$tld => $dbgRow], JSON_PRETTY_PRINT));
            }*/
            // 2022-05: plenty of tlds having reg terms that are not supported for renewal

            $data = [
                'zone' => $tld,
                'periods' => $zoneInfo->api->properties['PERIODS'][0] ?: $zoneInfo->api->properties['REGISTRATIONPERIODS'][0],
                'grace_days' => $zoneInfo->api->properties['AUTORENEWGRACEPERIODDAYS'][0] ?: 0,
                'redemption_days' => $zoneInfo->api->properties['REDEMPTIONPERIODDAYS'][0] ?: 0,
                'epp_required' => $zoneInfo->api->properties['AUTHCODE'][0] == 'required',
                'id_protection' => $zoneInfo->api->properties['RRPSUPPORTSWHOISPRIVACY'][0] || $zoneInfo->api->properties['SUPPORTSTRUSTEE'][0],
                'supports_renewals' => $zoneInfo->api->properties['RRPSUPPORTSRENEWAL'][0] ?: 0,
                'renews_on_transfer' => $zoneInfo->api->properties['RENEWALATTRANSFER'][0] == 1 || $zoneInfo->api->properties['RENEWALAFTERTRANSFER'][0] == 1,
                'handle_updatable' => $zoneInfo->api->properties['HANDLESUPDATEABLE'][0] == 1,
                'needs_trade' => strtoupper($zoneInfo->api->properties['OWNERCHANGEPROCESS'][0]) == 'TRADE',
                'updated_at' => date("Y-m-d H:i:s")
            ];
            ZoneModel::query()->updateOrCreate(["zone" => $tld], $data);

            $zone = ZoneModel::query()
                ->where('zone', $tld)
                ->first();
        }

        if ($zone instanceof ZoneModel) {
            return $zone;
        }
        throw new Exception("Unable to get zone info for tld $tld");
    }

    /**
     * Format Period String into usable format and filter out values unsupported by WHMCS
     * Reset Periods will also get nicely parsed into integers (e.g. R1Y)
     * @param string $periodStr Period String e.g. "1Y,2Y,3Y,4Y"
     * @return array<int, int>
     */
    public static function formatPeriods(string $periodStr): array
    {
        $periods = explode(",", $periodStr);
        // replace "R" of reset periods
        $p = preg_replace("/^R/", "", $periods);
        if ($p === null) {
            return [];
        }
        // filter out 1M period, not supported by whmcs at all
        $a = preg_grep("/^1M$/", $p, PREG_GREP_INVERT);
        if ($a === false) {
            return [];
        }
        // unique values, re-indexed, convert strings to ints
        return array_values(array_unique(array_map("intval", $a)));
    }

    /**
     * @param array<string, mixed> $params
     * @return object|null
     */
    public static function getForMigrator(array $params): ?object
    {
        $domainName = $params["sld"] . "." . $params["tld"];
        try {
            $zoneInfo = self::get($params);
            $registrationPeriods = self::formatPeriods($zoneInfo->periods);
            $renewalPeriods = $registrationPeriods;
            $transferPeriods = $registrationPeriods;
            $transferResetPeriods = $registrationPeriods;

            $isAfnicTLD = preg_match("/\.(fr|pm|re|tf|wf|yt)$/i", $domainName);
            $isAuTLD = preg_match("/\.au$/i", $domainName);
            $isCaUsTLD = preg_match("/\.(ca|us)$/i", $domainName);
            $contactsForTransfer = [];
            if ($isAfnicTLD) {
                $contactsForTransfer = ["ADMINCONTACT", "TECHCONTACT"];
            } elseif ($isAuTLD || (!$isCaUsTLD && $zoneInfo->needs_trade)) {
                $contactsForTransfer = ["OWNERCONTACT", "ADMINCONTACT", "TECHCONTACT", "BILLINGCONTACT"];
            }

            //TODO add missing info from zoneInfo: handle_updatable
            $data = [
                "tld" => [
                    "label" => $params["tld"],
                    "class" => null,
                    "isAFNIC" => $isAfnicTLD,
                    "repository" => null
                ],
                "registration" => [
                    "periods" => $registrationPeriods,
                    "defaultPeriod" => $registrationPeriods[0]
                ],
                "renewal" => [
                    "periods" => $renewalPeriods,
                    "defaultPeriod" => $renewalPeriods[0],
                    "explicit" => $zoneInfo->supports_renewals,
                    "graceDays" => $zoneInfo->grace_days
                ],
                "redemption" => [
                    "days" => $zoneInfo->redemption_days
                ],
                "transfer" => [
                    "periods" => $transferPeriods,
                    "resetPeriods" => $transferResetPeriods,
                    "defaultPeriod" => $transferPeriods[0], // evtl. 0Y
                    "isFree" => !$zoneInfo->renews_on_transfer,
                    "includeContacts" => !empty($contactsForTransfer),
                    "contacts" => $contactsForTransfer,
                    "requiresAuthCode" => $zoneInfo->epp_required
                ],
                "trade" => [
                    "required" => $zoneInfo->needs_trade,
                    "isStandard" => true,
                    "isIRTP" => false,
                    "triggerFields" => ["Registrant" => ["First Name", "Last Name", "Organization Name", "Email"]]
                ],
                "update" => [
                    "unlockWithAuthCode" => (bool)preg_match("/\.fi$/i", $params["tld"]),
                ],
                "addons" => [
                    "idprotection" => $zoneInfo->id_protection
                ],
                "registrant" => [
                    "changeBy" => null
                ]
            ];
            $json = json_encode($data);
            return $json ? json_decode($json) : null;
        } catch (Exception $ex) {
            return null;
        }
    }
}
