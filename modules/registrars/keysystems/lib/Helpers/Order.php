<?php

namespace WHMCS\Module\Registrar\RRPproxy\Helpers;

use Illuminate\Database\Capsule\Manager as DB;

class Order
{
    public int $userId;
    public int $contactId;
    /**
     * @var array<string>
     */
    public array $nameServers;

    public function __construct(string $domain)
    {
        $order = DB::table('tblorders as o')
            ->join('tbldomains as d', 'd.orderid', '=', 'o.id')
            ->where('d.domain', $domain)
            ->select('o.userid', 'o.contactid', 'o.nameservers')
            ->orderBy('o.id', 'DESC')
            ->first();

        if ($order != null) {
            $order = get_object_vars($order);
            $this->userId = $order["userid"];
            $this->contactId = $order["contactid"];
            $this->nameServers = explode(",", $order["nameservers"]);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function getContact()
    {
        return DB::table($this->contactId ? 'tblcontacts' : 'tblclients')
            ->where('id', $this->contactId ?: $this->userId)
            ->select(
                'firstname',
                'lastname',
                'address1',
                'address2',
                'city',
                'state',
                'country',
                'postcode',
                'phonenumber AS fullphonenumber',
                'email',
                'companyname'
            )
            ->first();
    }
}
