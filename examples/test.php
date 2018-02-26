<?php
require __DIR__.'/../vendor/autoload.php';

use Rexlabs\HyperHttp\Client as Hyper;
$response = Hyper::getRequest(
    'https://min-api.cryptocompare.com/data/pricehistorical',
    [
        'fsym' => 'ETH',
        'tsyms' => 'BTC,USD',
        'ts' => '1452680400',
        'extraParams' => 'your_app_name',
    ]
);
printf("Status code: %s\n", $response->getStatusCode());
printf("Is JSON: %s\n", $response->isJson() ? 'Yes' : 'No');
printf("ETH->USD: %s\n", $response->get('ETH.USD'));
printf("ETH->BTC: %s\n", $response->get('ETH.BTC'));
printf("Request URI: %s\n", $response->getRequest()->getUri());
echo $response->getRequest()->getCurl();