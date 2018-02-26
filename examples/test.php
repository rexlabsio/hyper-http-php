<?php
require __DIR__.'/../vendor/autoload.php';

$client = \Rexlabs\HyperHttp\Client::make();
$response = $client->get(
    'https://min-api.cryptocompare.com/data/pricehistorical',
    [
        'fsym' => 'ETH',
        'tsyms' => 'BTC,USD,EUR',
        'ts' => '1452680400',
        'extraParams' => 'your_app_name',
    ]
);
echo $response->get('ETH.USD');
$request = $response->getRequest();
echo (string)$request->getUri();