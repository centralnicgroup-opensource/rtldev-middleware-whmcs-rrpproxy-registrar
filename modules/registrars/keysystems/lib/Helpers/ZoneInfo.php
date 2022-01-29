<?php

namespace WHMCS\Module\Registrar\RRPproxy\Helpers;

use DateTime;
use Exception;
use WHMCS\Module\Registrar\RRPproxy\Commands\GetZoneInfo;
use WHMCS\Module\Registrar\RRPproxy\Models\ZoneModel;

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

            $data = [
                'zone' => $tld,
                'periods' => $zoneInfo->api->properties['PERIODS'][0],
                'grace_days' => $zoneInfo->api->properties['AUTORENEWGRACEPERIODDAYS'][0],
                'redemption_days' => $zoneInfo->api->properties['REDEMPTIONPERIODDAYS'][0],
                'epp_required' => $zoneInfo->api->properties['AUTHCODE'][0] == 'required',
                'id_protection' => $zoneInfo->api->properties['RRPSUPPORTSWHOISPRIVACY'][0] || $zoneInfo->api->properties['SUPPORTSTRUSTEE'][0],
                'supports_renewals' => $zoneInfo->api->properties['RENEWALPERIODS'][0] != 'n/a',
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
     * @return array<string, mixed>
     */
    public static function getForMigrator(array $params): array
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
            return $data;
        } catch (Exception $ex) {
            //TODO handle this somehow
            return [];
        }
    }
}
