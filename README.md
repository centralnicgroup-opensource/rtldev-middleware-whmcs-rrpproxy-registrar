# WHMCS "RRPproxy" Registrar Module #

[![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)
[![Build Status](https://travis-ci.com/rrpproxy/whmcs-rrpproxy-registrar.svg?branch=master)](https://travis-ci.com/rrpproxy/whmcs-rrpproxy-registrar)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PRs welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/blob/master/CONTRIBUTING.md)

This Repository covers the updated WHMCS Registrar Module of RRPproxy. Source code and latest package version are stable. Just the documentation is to be seen as WIP.

## Supported Features ##

* Domain Registration
  * Additional domain fields (Extensions)
* Domain Transfer
* Domain Management
  * WHOIS Contact Verification (IRTP)
  * Domain Locking (Transfer Lock)
  * Update Contact (Owner/Admin) Information
  * Nameservers Change
  * Child Nameserver Management (Add, Modify, Delete)
  * Explicit Deletions in Admin panel
* Domain Renewal
* IDNs Support (using idn_to_ascii)
* DNS Management (A, AAAA, MX, CNAME, TXT)
* Support for OTE testing environment
* Support for WHOIS Privacy / ID Protection
* Support for Bulk Update Operations
* Support for DNSSEC Management

## Unsupported Features ##

* Premium Domains
* Creating Owner/Admin Contacts on TransferDomain
* Automatic DNS Zone Creating/Deletion if DNS Management is checked

## Supported TLD-specific Additional Domain Fields ##

| TLD | Status |
| -------- | -------- |
| .au | Supported|
| .br | Supported|
| .ca | Supported|
| .cl | Supported|
| .de | Supported|
| .es | Supported|
| .eu | Supported|
| .fr | Supported|
| .it | Supported|
| .nu | Supported|
| .ro | Supported|
| .ru | Supported|
| .se | Supported|
| .sg | Supported|
| .uk | Supported|
| .us | Supported|

## Resources ##

* [Release Notes](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/releases)

## Installation / Update Guide ##

Simply upload the new version files by FTP or SCP.
Copy all files from the extracted archive to your WHMCS directory, while keeping the folder structure intact.

E.g.

`modules/registrars/rrpproxy/rrpproxy.php => $YOUR_WHMCS_ROOT/modules/registrars/rrpproxy/rrpproxy.php`

## Usage Guide ##

Go to your WHMCS Admin Area -> `Setup > Products/Services > Domain Registrars` and activate RRPProxy Registrar module by clicking the Activate button and enter your RRPProxy credentials (username/password).

## Minimum Requirements ##

* WHMCS 7.6

For the latest WHMCS minimum system requirements, please refer to
[https://docs.whmcs.com/System_Requirements](https://docs.whmcs.com/System_Requirements)

## Contributing ##

Please read and follow our [Contribution Guidelines](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/blob/master/CONTRIBUTING.md).

## Authors ##

* **Zoltan Egresi** - *development* - [egresi](https://github.com/egresi)
* **Kai Schwarz** - *development* - [PapaKai](https://github.com/papakai)

See also the list of [contributors](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/graphs/contributors) who participated in this project.

## License ##

This project is licensed under the MIT License - see the [LICENSE](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/blob/master/LICENSE) file for details.

[RRPproxy / Key-Systems GmbH](https://www.rrpproxy.net/)
