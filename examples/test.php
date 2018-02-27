<?php
require __DIR__ . '/../vendor/autoload.php';

use Rexlabs\HyperHttp\Hyper;

$response = Hyper::get('https://min-api.cryptocompare.com/data/pricehistorical', [
    'fsym' => 'ETH',
    'tsyms' => 'BTC,USD',
    'ts' => '1452680400',
]);

// Show historical price of ethereum in UTC and BTC currency.
printf("ETH->USD: %s\n", $response->get('ETH.USD'));
printf("ETH->BTC: %s\n", $response->get('ETH.BTC'));

// Request and response information
printf("Request URI: %s\n", $response->getRequest()->getUri());
printf("Status code: %s\n", $response->getStatusCode());
printf("Is JSON: %s\n", $response->isJson() ? 'Yes' : 'No');


echo $response->getRequest()->getCurl();