<?php

namespace WHMCS\Module\Registrar\Keysystems\Features;

use Exception;
use WHMCS\Module\Registrar\Keysystems\Commands\AddContact;
use WHMCS\Module\Registrar\Keysystems\Commands\ModifyContact;
use WHMCS\Module\Registrar\Keysystems\Commands\StatusContact;

class Contact
{
    /**
     * @param string $contactHandle
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function getContactInfo(string $contactHandle, array $params): array
    {
        try {
            $contact = new StatusContact($params, $contactHandle);
            return [
                "First Name" => $contact->api->properties["FIRSTNAME"][0],
                "Last Name" => $contact->api->properties["LASTNAME"][0],
                "Company Name" => $contact->api->properties["ORGANIZATION"][0],
                "Address" => $contact->api->properties["STREET"][0],
                "Address 2" => @$contact->api->properties["STREET"][1],
                "City" => $contact->api->properties["CITY"][0],
                "State" => $contact->api->properties["STATE"][0],
                "Postcode" => $contact->api->properties["ZIP"][0],
                "Country" => $contact->api->properties["COUNTRY"][0],
                "Phone" => $contact->api->properties["PHONE"][0],
                "Fax" => @$contact->api->properties["FAX"][0],
                "Email" => $contact->api->properties["EMAIL"][0]
            ];
        } catch (Exception $ex) {
            return [
                "First Name" => "",
                "Last Name" => "",
                "Company Name" => "",
                "Address" => "",
                "Address 2" => "",
                "City" => "",
                "State" => "",
                "Postcode" => "",
                "Country" => "",
                "Phone" => "",
                "Fax" => "",
                "Email" => ""
            ];
        }
    }

    /**
     * @param array<string, mixed> $contactDetails
     * @param array<string, mixed> $params
     * @return string Contact Handle
     * @throws Exception
     */
    public static function getOrCreateContact(array $contactDetails, array $params): string
    {
        $addContact = new AddContact($params, $contactDetails);
        $addContact->execute();
        return $addContact->getContactHandle();
    }

    /**
     * @param array<string, mixed> $contact
     * @param array<string, mixed> $params
     * @return string Contact Handle
     * @throws Exception
     */
    public static function getOrCreateOwnerContact(array $contact, array $params): string
    {
        $contactDetails = self::mapOwnerContact($contact);
        return self::getOrCreateContact($contactDetails, $params);
    }

    /**
     * @param array<string, mixed> $contact
     * @param array<string, mixed> $params
     * @return string
     * @throws Exception
     */
    public static function getOrCreateAdminContact(array $contact, array $params): string
    {
        $contactDetails = self::mapAdminContact($contact);
        return self::getOrCreateContact($contactDetails, $params);
    }

    /**
     * @param bool $supportsHandleUpdate
     * @param string $contact_id
     * @param array<string, mixed> $contactDetails
     * @param array<string, mixed> $params
     * @return string|null
     */
    public static function updateContact(bool $supportsHandleUpdate, string $contact_id, array $contactDetails, array $params): ?string
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
                $statusContact = new StatusContact($params, $contact_id);
                if (
                    $contact['firstname'] == $statusContact->api->properties['FIRSTNAME'][0]
                    && $contact['lastname'] == $statusContact->api->properties['LASTNAME'][0]
                    && $contact['organization'] == $statusContact->api->properties['ORGANIZATION'][0]
                ) {
                    $contact['contact'] = $contact_id;
                    $modifyContact = new ModifyContact($params, $contact);
                    $modifyContact->execute();
                    $needNewContact = false;
                }
            } catch (Exception $ex) {
                // We suffer in silence and create a new contact instead
            }
        }
        if ($needNewContact) {
            try {
                return self::getOrCreateContact($contact, $params);
            } catch (Exception $ex) {
                return null;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $params
     * @param string $prefix
     * @return array<string, mixed>
     */
    private static function mapContact(array $params, string $prefix = ''): array
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

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function mapOwnerContact(array $params): array
    {
        return self::mapContact($params, '');
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function mapAdminContact(array $params): array
    {
        return self::mapContact($params, 'admin');
    }
}
