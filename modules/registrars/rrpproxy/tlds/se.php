<?php
$tlds = ['se', 'com.se', 'tm.se'];

if (in_array($params['tld'], $tlds)) {
  $extensions['X-NICSE-IDNUMBER'] = $params['additionalfields']['Identification Number'];
  $extensions['X-NICSE-VATID'] = $params['additionalfields']['VAT'];
}
