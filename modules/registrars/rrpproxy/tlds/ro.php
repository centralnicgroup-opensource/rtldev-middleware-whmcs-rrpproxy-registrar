<?php
$tlds = ['ro', 'arts.ro', 'com.ro', 'firm.ro', '.info.ro', 'nom.ro', 'nt.ro', 'org.ro', 'rec.ro', 'store.ro', 'tm.ro', 'www.ro'];

if (in_array($params['tld'], $tlds)) {
    if ($params['additionalfields']['Registrant Type'] == 'p') {
        $extensions['X-RO-IDCARD-OR-PASSPORT-NUMBER'] = $params['additionalfields']['CNPFiscalCode'];
    } else {
        $extensions['X-RO-VAT-NUMBER'] = $params['additionalfields']['CNPFiscalCode'];
        $extensions['X-RO-COMPANY-NUMBER'] = $params['additionalfields']['Registration Number'];
    }
}
