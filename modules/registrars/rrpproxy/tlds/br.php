<?php

/* The CPF is the financial identity number provided by the Brazilian Government for every Brazilian citizen in order to charge taxes and financial matters.
The CNPJ is the same as the CPF but it works for companies.
CPF must be given in the following format: NNN.NNN.NNN-NN
CNPJ must be given in the following format: NN.NNN.NNN/NNNN-NN
*/

$extensions['X-BR-ACCEPT-TRUSTEE-TAC'] = '0';
$extensions['X-BR-REGISTER-NUMBER'] = $params["additionalfields"]["X-BR-REGISTER-NUMBER"];
