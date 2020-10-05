## [0.4.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.0...v0.4.1) (2020-10-05)


### Bug Fixes

* **transfersync:** fix contact query missing email ([9dfb9c0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/9dfb9c08cf5a5891e42d68a5d666fb24c55c77d4))

# [0.4.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.3.4...v0.4.0) (2020-09-21)


### Features

* **migration:** also migrate old config ([d7ed854](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/d7ed85427a4fef939ea94ebffcaccc98808d7430))
* **reg-transfer:** automatic transfer lock and nameserver configuration is now configurable ([0d95241](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/0d952412d1088a74be768b4a6d586d104dd193df))

## [0.3.4](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.3.3...v0.3.4) (2020-09-21)


### Performance Improvements

* **check-availability:** chunk tlds to reduce api calls and improve performance ([457896f](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/457896f5aefba810ed690c88e956d56046a02a7b))

## [0.3.3](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.3.2...v0.3.3) (2020-09-18)


### Bug Fixes

* **checkdomains:** fix check domains not working properly ([510a724](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/510a72416c2445544688fc324cb2f38c596bde18))

## [0.3.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.3.1...v0.3.2) (2020-09-14)


### Bug Fixes

* **semantic-release:** release test ([b80ee96](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/b80ee9616f876ba2acc96a736b1f12832d65e88a))

## [0.3.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.3.0...v0.3.1) (2020-09-14)


### Bug Fixes

* **test:** test ([76618db](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/76618dba2b9fd2af73f8f29e417e34ebd5e5f827))

# [0.3.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.2.1...v0.3.0) (2020-09-14)


### Bug Fixes

* **additional-fields:** fix path in additionalfields resource file ([b31e993](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/b31e993babff59c50c9a42d2f5ab9ef418f31f6b))
* **renewdomain:** fix domain renewal ([5bdc390](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/5bdc390ccd58393b453d976955b89f1f217a4b44))


### Features

* **config:** implement update checker ([87859b5](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/87859b5f4979a587abedc48399097e70a5fee3fb))
* **zoneinfo:** use zone info from api to determine features ([ee41939](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/ee41939e39ce8106168dd242f245f30a5fb9d560))

## [0.2.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.2.0...v0.2.1) (2020-09-06)


### Bug Fixes

* **semantic-release:** updateVersion.sh now works again on linux ([17b5dbc](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/17b5dbcb8aecd5682c94ddf6126c08843c7f4516))

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
