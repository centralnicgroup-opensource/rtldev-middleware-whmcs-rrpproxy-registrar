# [0.2.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.1.0...v0.2.0) (2020-09-05)


### Features

* **dnssec:** improve usability and add ability to enable/disable dnssec for clients ([d3120a2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/d3120a25ec1730faa96f09a560ada2482e0469fa))
* **domainreg:** improve contact handling in domain registrations ([083ec7e](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/083ec7ea943a591d61c53730675b38734c69fd7d))
* **GetContactDetails:** also return Admin/Billing/Tech contacts if WHMCS is set to use client details for those ([0c4f993](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/0c4f993cf1c9c6fa8cf658e75fcc5deea3fa59c6))
* **GetEPPCode:** call SetAuthcode for TLDs that require it ([ae4cfa6](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/ae4cfa6b7096f580a93c92eaeeec6bce0f9e450b))
* **keydns:** implement e-mail forwarding ([76146aa](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/76146aaff86dd556730ca687e995d15f63e3d9f9))
* **keydns:** implement web forwarding ([ba11058](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/ba110583bcb594ae8501215a003a4d97984287f2))
* **migration:** add button to migrate domains from stock module ([94eb726](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/94eb7262d820867d8dbf225655b808d63d366b36))
* **RenewDomain:** add safety measure to avoid renewing domains if they were already renewed ([74072ae](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/74072ae544ae435b25ee9b4535ec18b60e9b9daf))
* **RenewDomain:** set RENEWONCE instead of RENEWONCETHENAUTODELETE ([998f957](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/998f957a9ed389ec94ce5a8e2625672dba25d6a8))
* **RequestDelete:** Make deletion mode configurable and fallback to SetDomainRenewalmode if DeleteDomain fails ([91af06d](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/91af06de21962d9e944faf5f9f30231330f11ede))
* **SaveContactDetails:** add support for domain trade, contact handle update, and updating admin/billing/tech contacts ([4b98625](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/4b986257491cbb5968b2468ae509c38a505ce8ad))
* **tldsync:** implement tld pricing sync ([4b1b48f](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/4b1b48fd9e732b977046139b124db1fe333f8e4c))
* **TransferSync:** set contacts and nameservers after transfer ([cc541eb](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/cc541eba87c7c60ab983d32c2d11b2266479f780))
