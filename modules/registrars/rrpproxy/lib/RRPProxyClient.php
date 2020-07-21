<?php
/**
 * WHMCS RRPProxy API Client
 *
 * @author Zoltan Egresi <egresi@globehosting.com>
 * Copyright 2019
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

use WHMCS\Database\Capsule;

class RRPProxyClient
{

    protected $params;

    public function __construct()
    {
        $registrar = Capsule::table('tblregistrars')->where('registrar', 'rrpproxy')->get();
        if (empty($registrar)) {
            throw \Exception('Registrar data not found');
        }
        $params = [];
        foreach ($registrar as $data) {
            $params[$data->setting] = self::decrypt($data->value);
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
            $values["First Name"] = $response["property"]["firstname"][0];
            $values["Last Name"] = $response["property"]["lastname"][0];
            $values["Company Name"] = $response["property"]["organization"][0];
            $values["Address"] = $response["property"]["street"][0];
            $values["Address 2"] = $response["property"]["street"][1];
            $values["City"] = $response["property"]["city"][0];
            $values["State"] = $response["property"]["state"][0];
            $values["Postcode"] = $response["property"]["zip"][0];
            $values["Country"] = $response["property"]["country"][0];
            $values["Phone"] = $response["property"]["phone"][0];
            $values["Fax"] = $response["property"]["fax"][0];
            $values["Email"] = $response["property"]["email"][0];
        } catch (\Exception $ex) {
            return [];
        }
        return $values;
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
            CURLOPT_USERAGENT       => "WHMCS (" . PHP_OS . "; " . php_uname('m') . "; rv:rrpproxy/".RRPPROXY_VERSION.") php/" . implode(".", [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]),
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
        logModuleCall('rrpproxy', $command, $params, $response, $results, array($params['username'], $params['password']));


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
