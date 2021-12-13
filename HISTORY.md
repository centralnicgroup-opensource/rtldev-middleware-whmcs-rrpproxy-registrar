# [0.12.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.11.2...v0.12.0) (2021-12-13)


### Bug Fixes

* **idn conversion:** reviewed ([37da0eb](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/37da0ebde9a75fd0dd1130083acb15525d77a7e8)), closes [#68](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/68)
* **zoneinfo:** return error if no zone info and request failed ([58f4aab](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/58f4aaba01e7fcbd984a9e413cffa5f10e7c2a5c))


### Features

* **migrator:** add GetZoneFeatures function for cnic-migrator ([fee394e](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/fee394e7ded46e32512b58dce48495d2dbe16bfb))

## [0.11.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.11.1...v0.11.2) (2021-11-30)


### Bug Fixes

* **transfer:** fix transfer request ([d172fcf](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/d172fcf6805f55d65b2fa61e5be80275abba380c))

## [0.11.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.11.0...v0.11.1) (2021-11-30)


### Bug Fixes

* **params:** no longer use undocumented domainname parameter ([5c1f5fd](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/5c1f5fd03b752142db5f9a96abedfe13719a4cce))

# [0.11.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.10.2...v0.11.0) (2021-11-28)


### Bug Fixes

* **transfer-sync:** skip modify domain if not necessary ([0493843](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/04938434b46063894b7511b7b3db5e79c6c6ee8f))


### Features

* **transfer-sync:** only update nameservers if necessary ([e90e6bf](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/e90e6bfb119c8330df40468cc352dff0bb372235))

## [0.10.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.10.1...v0.10.2) (2021-11-28)


### Bug Fixes

* **tld-sync:** fix db issue on tld pricing sync ([f44872b](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/f44872b05b3150ce48d4d04673b2ea3b47b41d97)), closes [#63](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/63)

## [0.10.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.10.0...v0.10.1) (2021-11-02)


### Bug Fixes

* **contacts:** allow to modify contacts when existing ones are invalid ([268369b](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/268369b56d8f145ae89677b9ade7ab6e879212c9))
* **ote:** whois privacy is not enabled in ot&e accounts ([e5ebbb8](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/e5ebbb8c88419723b76ad626d7cd8e3d9d6fdc56))

# [0.10.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.9.0...v0.10.0) (2021-11-02)


### Features

* **pricing:** add options to auto tenable DNS management, email forwarding and ID protection ([9b9056e](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/9b9056e972efde7d07ee8c321152abc11752a466))

# [0.9.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.6...v0.9.0) (2021-11-02)


### Features

* **transfer:** improve domain transfer handling ([b72bb0d](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/b72bb0d49a5641925e94d0325d219210a829c167)), closes [#56](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/56)

## [0.8.6](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.5...v0.8.6) (2021-09-17)


### Bug Fixes

* **dns-management:** fix dns zone failing to enable in dns management ([22eb4f2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/22eb4f25eece932bc61c1442e6bb2b5c9f6def0e))

## [0.8.5](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.4...v0.8.5) (2021-08-31)


### Bug Fixes

* **transfer:** method toArray not available, fetch object vars ([d531c40](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/d531c402e5324e7a7d94794d82474560cb80462f))

## [0.8.4](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.3...v0.8.4) (2021-08-27)


### Bug Fixes

* **transfer:** fixed issue with stdClass in contact handling in newer WHMCS releases ([deb3a9b](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/deb3a9b031255d8d04a45356c93dcd0b5af8ecdf))

## [0.8.3](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.2...v0.8.3) (2021-08-04)


### Bug Fixes

* **transfer:** fix transfer sync with no contact in WHMCS v7 ([967f8c7](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/967f8c7dad8ee0e8780960f354bcdf6ca73ea581)), closes [#49](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/49)

## [0.8.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.1...v0.8.2) (2021-08-04)


### Bug Fixes

* **getdomaininformation:** let GetDomainInformation throw exception ([c8fa368](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/c8fa368724ae9c7e440417accf45a90580c0d676)), closes [#51](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/51)

## [0.8.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.8.0...v0.8.1) (2021-08-04)


### Bug Fixes

* **getdomaininformation:** reviewed returned custom data ([152343d](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/152343d71145cf4196380ef05a4a634ec6fe4134))

# [0.8.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.7.3...v0.8.0) (2021-08-04)


### Features

* **getdomaininformation:** fixes index access; extended to provide add. data for import purposes ([fd727ff](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/fd727ff5b74b31313cb9b1fb05fc4c1e0e19f2cd))

## [0.7.3](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.7.2...v0.7.3) (2021-07-24)


### Bug Fixes

* **log:** wrong parameter names in logModuleCall ([e8de9b8](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/e8de9b85a4ad6bf290819a65b8af5456860ad52e)), closes [#42](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/42)

## [0.7.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.7.1...v0.7.2) (2021-07-24)


### Bug Fixes

* **transfer:** use forcerequest parameter to automatically switch to usertransfer if needed ([f31fc51](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/f31fc51b9abe84bc006545c3fddcecefadc5419f)), closes [#44](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/44)

## [0.7.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.7.0...v0.7.1) (2021-07-23)


### Bug Fixes

* **.ro additional fields:** make CNPFiscalCode mandatory ([1480c88](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/1480c882d9711f1bbc274d0c18b37b1dc8218eaa))

# [0.7.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.6.0...v0.7.0) (2021-07-23)


### Bug Fixes

* **getdomaininformation:** improve handling of CheckDNSZone ([ca60494](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/ca60494ed2a561afeb28ff4186da29bd7450b64a))


### Features

* **getdomaininformation:** extended to also return addon status ([3603b96](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/3603b96227fc6a577d109c306172cd7bb6a6f3fd))

# [0.6.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.5.3...v0.6.0) (2021-04-28)


### Features

* **dnssec:** remove SHA-1 options from DNSSEC ([af819f9](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/af819f93bc54805e9b2c180073265166ac1bf855))

## [0.5.3](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.5.2...v0.5.3) (2021-02-05)


### Bug Fixes

* **transfer:** fix .eu transfer handling ([2fb3fe4](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/2fb3fe4e80b34fb77e7c7c8b657d2b18865c5efc)), closes [#38](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/38)

## [0.5.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.5.1...v0.5.2) (2021-01-28)


### Bug Fixes

* **ci:** migration to github actions and gulp (vs. Travis CI and make) ([fa18297](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/fa182970b2d333cf35dd50a9de173521c8a3b30c))

## [0.5.1](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.5.0...v0.5.1) (2021-01-14)


### Bug Fixes

* **semantic-release:** update packages to fix issue with whmcs marketplace ([444f263](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/444f2639c2bf723e4b17234c618bd89ba87971ad))

# [0.5.0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.6...v0.5.0) (2021-01-08)


### Features

* **logo:** update RRPproxy logo ([7176e8e](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/7176e8e08e11914d02087809f349766e302b57d3))

## [0.4.6](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.5...v0.4.6) (2020-11-14)


### Performance Improvements

* **config:** reduce strain on db in domain list page ([47fc247](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/47fc24766e35231c6438f357fa97c9d53e95c5bc))

## [0.4.5](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.4...v0.4.5) (2020-11-13)


### Bug Fixes

* **transfer:** order clause ambiguous field clarify ([3f16b25](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/3f16b25471a5a78046af4b7da97daaedf417b644))
* **transfer:** wrong join syntax cause cron crash ([0c769f4](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/0c769f4a91a18f9858437cf54d0dfe6a48dfc1c2))

## [0.4.4](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.3...v0.4.4) (2020-11-01)


### Bug Fixes

* **tld-eu:** remove X-ACCEPT-QUARANTINE and add X-EU-REGISTRANT-CITIZENSHIP parameter ([35fd055](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/35fd0553bdbd09fc7505c5af5051d677c654a650))
* **updatecontact:** invalid array key for contact company field ([ea96252](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/ea962522a2e6eac9f40d086b073271eed486e9ce))

## [0.4.3](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.2...v0.4.3) (2020-10-26)


### Bug Fixes

* **udpatecontact:** invalid array key for contact street field ([90ce94f](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/90ce94fb9d099e565308aa55e1d225d95f8f694f))

## [0.4.2](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/compare/v0.4.1...v0.4.2) (2020-10-23)


### Bug Fixes

* **renewdomain:** remove logic that could lower the renewal years ([d9718c0](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/commit/d9718c0a11b05b0a50d9c32438a22fa534de94eb)), closes [#25](https://github.com/rrpproxy/whmcs-rrpproxy-registrar/issues/25)

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
