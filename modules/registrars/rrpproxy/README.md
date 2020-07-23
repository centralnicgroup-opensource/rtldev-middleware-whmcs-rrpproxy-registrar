# WHMCS RRPProxy Registrar Module #

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

## Installation ##

Copy all files from the extracted archive to your WHMCS directory, while keeping the folder structure intact.

E.g.

`modules/registrars/rrpproxy/rrpproxy.php => $YOUR_WHMCS_ROOT/modules/registrars/rrpproxy/rrpproxy.php`

## Update Guide ##

Same as installation, simply upload the new version files via FTP.

## Usage Guide ##

Go to your WHMCS Admin -> Setup > Products/Services > Domain Registrars and activate RRPProxy Registrar module by clicking the Activate button and enter your RRPProxy credentials (username/password).

## Minimum Requirements ##

* WHMCS 7.6

For the latest WHMCS minimum system requirements, please refer to
[https://docs.whmcs.com/System_Requirements](https://docs.whmcs.com/System_Requirements)

## License ##

This project is licensed under the MIT License - see the LICENSE file for details.
