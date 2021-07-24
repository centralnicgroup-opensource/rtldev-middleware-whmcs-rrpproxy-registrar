<?php

/**
 * WHMCS RRPproxy API Client
 *
 * @author Sebastian Vassiliou <svassiliou@hexonet.net>
 * Copyright 2020 Key-Systems GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *
 */

namespace WHMCS\Module\Registrar\RRPProxy;

use Illuminate\Database\Capsule\Manager as DB;

class RRPProxyClient
{

    protected $params;

    public function __construct($params)
    {
        if (!isset($params['Username'])) {
            $registrar = DB::table('tblregistrars')->where('registrar', 'keysystems')->get();
            if (empty($registrar)) {
                throw \Exception('Registrar data not found');
            }
            foreach ($registrar as $data) {
                $params[$data->setting] = self::decrypt($data->value);
            }
        }
        if ($params['TestMode']) {
            $this->api_url = 'https://api-ote.rrpproxy.net/api/call?s_opmode=OTE&s_login=' . rawurlencode($params['Username']) . '&s_pw=' . rawurlencode($params['TestPassword']);
        } else {
            $this->api_url = 'https://api.rrpproxy.net/api/call?s_login=' . rawurlencode($params['Username']) . '&s_pw=' . rawurlencode($params['Password']);
        }
    }

    public static function decrypt($encrypted_string)
    {
        $command = 'DecryptPassword';
        $postData = array(
            'password2' => $encrypted_string,
        );
        $results = localAPI($command, $postData);
        return html_entity_decode($results['password']);
    }

    /**
     * Encrypt $decrypted_string
     *
     * @param string $decrypted_string The decrypted string
     * @return string $encrypted_string
     * */
    public static function encrypt($decrypted_string)
    {
        $command = 'EncryptPassword';
        $postData = array(
            'password2' => $decrypted_string,
        );
        $results = localAPI($command, $postData);

        return $results['password'];
    }

    public function getContactInfo($contact)
    {
        try {
            $response = $this->call('StatusContact', ['contact' => $contact]);
            return [
                "First Name" => $response["property"]["firstname"][0],
                "Last Name" => $response["property"]["lastname"][0],
                "Company Name" => $response["property"]["organization"][0],
                "Address" => $response["property"]["street"][0],
                "Address 2" => @$response["property"]["street"][1],
                "City" => $response["property"]["city"][0],
                "State" => $response["property"]["state"][0],
                "Postcode" => $response["property"]["zip"][0],
                "Country" => $response["property"]["country"][0],
                "Phone" => $response["property"]["phone"][0],
                "Fax" => @$response["property"]["fax"][0],
                "Email" => $response["property"]["email"][0]
            ];
        } catch (\Exception $ex) {
            return [];
        }
    }

    private function mapContact($params, $prefix = '')
    {
        $ownerContact = [
            'firstname' => $params[$prefix . 'firstname'],
            'lastname' => $params[$prefix . 'lastname'],
            'email' => $params[$prefix . 'email'],
            'street0' => $params[$prefix . 'address1'],
            'street1' => $params[$prefix . 'address2'],
            'city' => $params[$prefix . 'city'],
            'state' => $params[$prefix . 'state'],
            'zip' => $params[$prefix . 'postcode'],
            'country' => $params[$prefix . 'country'],
            'phone' => $params[$prefix . 'fullphonenumber'],
            'new' => 0,
            'preverify' => 1,
            'autodelete' => 1
        ];
        if ($params[$prefix . 'companyname']) {
            $ownerContact['organization'] = $params[$prefix . 'companyname'];
        }
        return $ownerContact;
    }

    public function mapOwnerContact($params)
    {
        return $this->mapContact($params, '');
    }

    public function mapAdminContact($params)
    {
        return $this->mapContact($params, 'admin');
    }

    public function getOrCreateContact($contactDetails)
    {
        $contact = $this->call('AddContact', $contactDetails);
        return $contact['property']['contact']['0'];
    }

    public function getOrCreateOwnerContact($params)
    {
        $contactDetails = $this->mapOwnerContact($params);
        return $this->getOrCreateContact($contactDetails);
    }

    public function getOrCreateAdminContact($params)
    {
        $contactDetails = $this->mapAdminContact($params);
        return $this->getOrCreateContact($contactDetails);
    }

    public function updateContact($supportsHandleUpdate, $contact_id, $contactDetails)
    {
        $contact = [
            //'validation' => true,
            'firstname' => $contactDetails['First Name'],
            'lastname' => $contactDetails['Last Name'],
            'organization' => $contactDetails['Company Name'],
            'street' => $contactDetails['Address'],
            'city' => $contactDetails['City'],
            'state' => $contactDetails['State'],
            'country' => $contactDetails['Country'],
            'zip' => $contactDetails['Postcode'],
            'phone' => $contactDetails['Phone'],
            'fax' => $contactDetails['Fax'],
            'email' => $contactDetails['Email']
        ];

        $needNewContact = true;
        if ($supportsHandleUpdate) {
            try {
                $response = $this->call('StatusContact', ['contact' => $contact_id]);
                if (
                    $contact['firstname'] == $response['property']['firstname'][0]
                    && $contact['lastname'] == $response['property']['lastname'][0]
                    && $contact['organization'] == $response['property']['organization'][0]
                ) {
                    $contact['contact'] = $contact_id;
                    $this->call('ModifyContact', $contact);
                    $needNewContact = false;
                }
            } catch (\Exception $ex) {
                $needNewContact = true;
            }
        }
        if ($needNewContact) {
            try {
                $response = $this->call('AddContact', $contact);
                return $response['property']['contact'][0];
            } catch (\Exception $ex) {
                return null;
            }
        }
        return null;
    }

    public function getZoneInfo($tld)
    {
        $maxDays = 30;
        $maxUpdates = 100;
        $updates = DB::table('mod_rrpproxy_zones')
            ->where('updated_at', '>', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->count();

        $zone = DB::table('mod_rrpproxy_zones')
            ->where('zone', $tld)
            ->first();

        $updateNeeded = false;
        if ($zone) {
            $curDate = new \DateTime();
            try {
                $zoneDate = new \DateTime($zone->updated_at);
                $dateDiff = $zoneDate->diff($curDate);
                if ($dateDiff->format('%r%a') > $maxDays) {
                    $updateNeeded = true;
                }
            } catch (\Exception $e) {
                $updateNeeded = true;
            }
        }

        if (!$zone || ($updateNeeded && $updates < $maxUpdates)) {
            try {
                $result = $this->call('GetZoneInfo', ['ZONE' => $tld]);
            } catch (\Exception $ex) {
                return $zone;
            }

            if (!is_array($result)) {
                return ['error' => 'GetZoneInfo - No response'];
            }

            $data = [
                'zone' => $tld,
                'periods' => $result['property']['periods'][0],
                'grace_days' => $result['property']['autorenewgraceperioddays'][0],
                'redemption_days' => $result['property']['redemptionperioddays'][0],
                'epp_required' => $result['property']['authcode'][0] == 'required',
                'id_protection' => $result['property']['rrpsupportswhoisprivacy'][0] || $result['property']['supportstrustee'][0],
                'supports_renewals' => $result['property']['renewalperiods'][0] != 'n/a',
                'renews_on_transfer' => $result['property']['renewalattransfer'][0] == 1 || $result['property']['renewalaftertransfer'][0] == 1,
                'handle_updatable' => $result['property']['handlesupdateable'][0] == 1,
                'needs_trade' => strtoupper($result['property']['ownerchangeprocess'][0]) == 'TRADE',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            DB::table('mod_rrpproxy_zones')->updateOrInsert(['zone' => $tld], $data);

            $zone = DB::table('mod_rrpproxy_zones')
                ->where('zone', $tld)
                ->first();
        }

        return $zone;
    }

    public function call($command, $params = array())
    {
        //doing IDN Conversion
        $idnConvertDomainCommands = ['AddDomain', 'ModifyDomain', 'RenewDomain', 'TransferDomain', 'StatusDomain', 'DeleteDomain', 'PushDomain'];
        $idnConvertDNSCommands = ['AddDNSZone', 'ModifyDNSZone', 'QueryDNSZoneRRList'];

        if (function_exists("idn_to_ascii")) {
            if (in_array($command, $idnConvertDomainCommands)) {
                $idnDomain = idn_to_ascii($params['domain']);
                $params['domain'] = ($idnDomain ? $idnDomain : $params['domain']);
            }
            if (in_array($command, $idnConvertDNSCommands)) {
                $dnszoneIDN = idn_to_ascii($params['dnszone']);
                $params['dnszone'] = ($dnszoneIDN ? $dnszoneIDN : $params['dnszone']);
            }
        }
        $url = $this->api_url;
        $params['command'] = $command;
        foreach ($params as $key => $val) {
            $url .= '&' . rawurlencode($key) . '=' . rawurlencode($val);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT         => 20,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_USERAGENT       => "WHMCS (" . PHP_OS . "; " . php_uname('m') . "; rv:rrpproxy/" . RRPPROXY_VERSION . ") php/" . implode(".", [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]),
            CURLOPT_REFERER         => $GLOBALS["CONFIG"]["SystemURL"],
            CURLOPT_HTTPHEADER      =>  [
                'Expect:',
                'Content-type: text/html; charset=UTF-8',
            ]
        ]);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Connection Error: ' . curl_errno($ch) . ' - ' . curl_error($ch));
        }

        curl_close($ch);

        $results = $this->processResponse($response);
        logModuleCall('keysystems', $command, $params, $response, $results, array($params['Username'], $params['Password']));


        if ((preg_match('/^2/', $results['code']))) { //Successful Return Codes (2xx), return the results.
            $this->retry = false;
            return $results;
        } elseif ((preg_match('/^4/', $results['code'])) && !$this->retry) { //Temporary Error Codes (4xx), we do a retry .
            $this->retry = true;
            sleep(60);
            return $this->call($command, $params);
        } else { //Permanent Error Codes (5xx), throw exception.
            throw new \Exception($results['description']);
        }
    }

    private function processResponse($response)
    {
        if (is_array($response)) {
            return $response;
        }

        if (empty($response)) {
            throw new \Exception('Empty response from API');
        }

        $hash = array("property" => array());
        $rlist = explode("\n", $response);
        foreach ($rlist as $item) {
            if (preg_match("/^([^\\=]*[^\t\\= ])[\t ]*=[\t ]*(.*)\$/", $item, $m)) {
                list(, $attr, $value) = $m;
                $value = preg_replace("/[\t ]*\$/", "", $value);
                if (preg_match("/^PROPERTY\\[([^\\]]*)\\]/i", $attr, $m)) {
                    $prop = strtolower($m[1]);
                    $prop = preg_replace("/\\s/", "", $prop);
                    if (in_array($prop, array_keys($hash["property"]))) {
                        array_push($hash["property"][$prop], $value);
                    } else {
                        $hash["property"][$prop] = array($value);
                    }
                } else {
                    $hash[$attr] = $value;
                }
            }
        }
        if (is_array($hash['property']) && count($hash['property']) === 0) {
            unset($hash['property']);
        }
        return $hash;
    }
}
