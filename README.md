# Hyper Http Client

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.org/rexlabsio/hyper-http-php.svg?branch=master)](https://travis-ci.org/rexlabsio/hyper-http-php)
[![Code Coverage](https://scrutinizer-ci.com/g/rexlabsio/hyper-http-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rexlabsio/hyper-http-php/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/rexlabs/hyper-http.svg)](https://packagist.org/packages/rexlabs/hyper-http)


## Overview

Hyper is an HTTP Client that aims to provide a simple, but powerful interface for making HTTP calls and fetching and manipulating API data.

## Why use Hyper

* Extremely simple interface `Hyper::get('http://some/url')`.
* Also supports object style `Hyper::make(...)->get('http://some/url')`.
* Provides a `Response` object which provides useful information like HTTP status code, body and headers.
* Every `Response` mixes in [rexlabs\array-object](https://packagist.org/packages/rexlabs/array-object) which allows you to
easily interrogate API responses.
* Throws a limited set of exceptions (with access to the request and/or response) when things go wrong.
* You have access to the original `Request` via `$response->getRequest()`.
* Supports all of [Guzzle client](https://packagist.org/packages/guzzlehttp/guzzle) functionality including streams.
* Allows you to dump cURL requests for reproducing from the command-line.
* Easily log all requests

## Usage

```php
<?php
use Rexlabs\HyperHttp\Hyper;

$response = Hyper::get('http://openlibrary.org/subjects/love.json');

// The first book for 'love' is: Wuthering Heights
echo "The first book for '{$response->name}' is: {$response->works->first()->title}\n";

echo "Total works: {$response->works->count()} books\n";
```


## Installation

To install in your project:

```bash
composer require rexlabs/hyper-http
```

### Dependencies

- PHP 7.0 or above.
- [guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/guzzle)
- [rexlabs/array-object](https://packagist.org/packages/rexlabs/array-object)
- [psr/log](https://packagist.org/packages/psr/log)
- [rtheunissen/guzzle-log-middleware](https://packagist.org/packages/rtheunissen/guzzle-log-middleware)
- [namshi/cuzzle](https://packagist.org/packages/namshi/cuzzle)


## Examples

The RESTful methods all return a `Response` object which makes interacting with responses simple.

### Example: Using static methods

```php
<?php
use Rexlabs\HyperHttp\Hyper;

$response = Hyper::get('https://example.com/url');
echo 'Status Code: '.$response->getStatusCode()."\n";
echo (string)$response; // Output the response body
```

### Example: Working with a JSON API

Since responses mixin [ArrayObject](https://packagist.org/packages/rexlabs/array-object) you can
easily fetch and manipulate values from the response:

```php
<?php
use Rexlabs\HyperHttp\Hyper;

// Fetch historical price via CryptoCompare's public API for Ethereum
$response = Hyper::get('https://min-api.cryptocompare.com/data/pricehistorical', [
    'fsym' => 'ETH',
    'tsyms' => 'BTC,USD',
    'ts' => '1452680400',
]);

// Output prices
printf("ETH->USD: %s\n", $response->get('ETH.USD'));
printf("ETH->BTC: %s\n", $response->get('ETH.BTC'));
```

### Example: Set global headers and pass in a logger

Use `make()` to simplify instantiation and then setup the object
for future requests:

```php
<?php
use Rexlabs\HyperHttp\Hyper;
use Rexlabs\Logger\CustomLogger;

$hyper = Hyper::make()
    ->setBaseUri('http://example.com/api/v1')
    ->setHeader('X-App-Identity', 'Some App')
    ->setHeader('X-Correlation-Id', '12345')
    ->setLogger(new CustomLogger);
```

* `$hyper = Hyper::make(array $config = [], \GuzzleHttp\Client $guzzle, \Psr\Log\LoggerInterface $logger)`

### Example: Instantiation via constructor

To get complete control over instantiation, use the constructor and
pass in a Guzzle instance:

```php

<?php
use Rexlabs\HyperHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\NullLogger;

$hyper = new Client(new GuzzleClient(), new NullLogger(), [
   'base_uri' => 'http://example.com/api/v1',
   'headers' => [
       'X-App-Identity' => 'Some App',
   ],
]);
$response = $hyper->get('/messages');
```

### Example: Dumping a cURL request

You can easily generate a cURL request for running from the command-line to reproduce your last request:

```php
<?php
use Rexlabs\HyperHttp\Hyper;

echo Hyper::get('https://example.com/api/v1/resources')
    ->getCurlRequest();
```

Output:

```bash
curl \
  'https://min-api.cryptocompare.com/data/pricehistorical?fsym=ETH&tsyms=BTC%2CUSD&ts=1452680400&extraParams=your_app_name' \
  -H 'Content-Type: application/json' -H 'Accept: application/json'
```

## Http Methods

Hyper provides the following methods for interacting with remote endpoints:

### get()

`get(mixed $uri, array $query = [], array $headers = [], array $options = []): Response`

Send an HTTP GET request, and return the Response:

```php
$response = Hyper::get('https://example.com', ['sort' => 'latest'], ['X-Greeting' => 'Hello!']);

$response = $hyper->get('/v1/people');
```

- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$query` is an optional array of query parameters which will be appended to the uri.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.


### post()

`post(mixed $uri, mixed $body = null, array $headers = [], array $options = []): Response`

Send an HTTP POST request, and return the Response:

```php
$response = Hyper::post('https://example.com/fruit', 'apples');

$response = $hyper->post('/v1/people', ['name' => 'Bob', 'age' => 25]);
```

- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$body` is the payload. If you provide an array, it will be converted and transported as json.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.

Alternative methods:

* `$response = $hyper->postForm($uri, $formParams, $headers, $options);`
* `$response = $hyper->postMultipartForm($uri, $formParams, $headers, $options);`


### put()

`put(mixed $uri, mixed $body = null, array $headers = [], array $options = []): Response`

Send an HTTP PUT request, and return the Response:

```php
$response = Hyper::put('https://example.com/fruit', 'apples');

$response = $hyper->put('/v1/people', ['name' => 'Bob', 'age' => 25]);
```

- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$body` is the payload. If you provide an array, it will be converted and transported as json.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.


### patch()

`patch(mixed $uri, mixed $body = null, array $headers = [], array $options = []): Response`

Send an HTTP PATCH request, and return the Response:

```php
$response = Hyper::patch('https://example.com/fruit', 'apples');

$response = $hyper->patch('/v1/people', ['name' => 'Bob', 'age' => 25]);
```

- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$body` is the payload. If you provide an array, it will be converted and transported as json.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.

### delete()

`delete(mixed $uri, mixed $body = null, array $headers = [], array $options = []): Response`

Send an HTTP DELETE request, and return the Response:

```php
$response = Hyper::delete('https://example.com/fruit', 'apples');

$response = $hyper->delete('/v1/people/1');
```

- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$body` is the optional payload. If you provide an array, it will be converted and transported as json.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.

### call()

`call(string $method, mixed $uri, mixed $body, array $headers, array $options): Response`

Send a generic HTTP request by specifying the `method` as the first argument.

```php
// Statically
$response = Hyper::call('MOVE', 'myfile1234', ['new_location' => 'some_folder']);

// Http method verbs may also be invoked via method name
$response = Hyper::move('myfile1234', ['new_location' => 'some_folder']);
$response = Hyper::somethingelse(...);

// Via object
$response = $hyper->call('MOVE', 'myfile1234', ['new_location' => 'some_folder']);
```

- `$method` is the HTTP verb. Eg. `GET` or something not part of the standard.
- `$uri` is a string or a `Uri`. If the string is not absolute it will be appended to the base uri.
- `$body` is the optional payload. If you provide an array, it will be converted and transported as json.
- `$headers` is an optional array of headers (indexed by header name) that will be merged with any global headers.
- `$options` is an optional array of Guzzle client options.

## Request Methods

Methods available from the `Rexlabs\HyperHttp\Message\Request` object:

### getUri()

Return the UriInterface object which encapsulates the URI/URL for this request.

### getMethod()

Return the HTTP method verb for this Request.

### getHeaders()

Retur  an array of headers for this `Request`

### getCurl()

Return a cURL request (string) suitable for running from the command-line. Useful for debugging requests.

## Response Methods

Methods available from the `Rexlabs\HyperHttp\Message\Response` object:

### getRequest()

Return the `Rexlabs\HyperHttp\Message\Request` object associated with the `Response`

### getCurlRequest()

Return a cURL request (string) suitable for running from the command-line. Useful for debugging requests.

### getStatusCode()

Return the HTTP status code for this `Response`. EG. 200

### getReasonPhrase()

Return the HTTP reason phrase associated with the status code.  EG. "OK" 

### isJson()

Returns `true` if this is a JSON response.

### toArray()

Converts a JSON response to an array and returns the array.

### toObject()

Converts a JSON response to an `ArrayObject`

### ArrayObject

Every `Response` object has all of the methods and functionality of the `ArrayObject` class from the `rexlabs\array-object` package.

This means based on the following response payload:

```json
{
  "books": [
    {
      "id": 1,
      "title": "1984",
      "author": "George Orwell"
    },
    {
      "id": 2,
      "title": "Pride and Prejudice",
      "author": "Jane Austen"
    }
  ]
}
```

You can perform the following functions:

```php
$response->books; // Instance of ArrayObject
$response->books->pluckArray('author'); // array [ 'George Orwell', 'Jane Austen' ]
$response->pluckArray('books.author'); // array [ 'George Orwell', 'Jane Austen' ]
$response->books->count(); // 2
$response->books->isCollection(); // true
$response->books[0]; // Instance of ArrayObject
$response->books[0]->isCollection(); // false
$response->books[0]->id; // 1
$response->get('books.1.title');    // "Pride and Prejudice"
foreach ($response->books as $book) {
    echo "{$book->title} by {$book->author}\n";
}
```

You can also call:

```php
$obj = $response->toObject();   // Instance of Arraybject
```

## Config

Set default config for all client's (defaults to [])

```php
Hyper::setDefaultConfig($config);
```

Set config for this client (values will override / merge with default)

```php
$client = Hyper::make($config);
```

### Default Logger

Set the default logger used by all clients that don't provide one.  
Must implement `LoggerInterface` (defaults to `NullLogger`)

```php
Hyper::setDefaultLogger($logger);
```

### Log Curl

Log the curl string for all requests (requires a logger set)

```php
$config = [
    'log_curl' => true,
];
```

### Guzzle config

Set the config passed to the underlying `GuzzleClient`

```php
$config = [
    'guzzle' => [
        'verify' => false,
    ],
];

// Set for all clients
Hyper::setDefaultConfig($config);

// Set for one client
$client = Hyper::make($config);
```

## Tests

To run tests:
```bash
composer tests
```

To run coverage report:
```bash
composer coverage
```
Coverage report is output to `./tests/report/index.html`

## Extending

Hyper allows extension for custom clients by:

- Storing separate instances for each subclass of Hyper for static use
    - Static use of `MyHyperSubclass` will return the correct instance created by `MyHyperSubclass`
    - Static use of `Hyper` will return the correct instance created by `Hyper`
- Override `protected static function makeClient` to customise client class (eg replace `new Client` with `new MyClient`)
- Override `protected static function makeConfig` to customise default client config
- Override `protected static function makeGuzzleConfig` to customise default guzzle client
- Override `protected static function getBaseUri` to provide a default base_uri to the client

## Contributing

Contributions are welcome, please submit a pull-request or create an issue.
Your submitted code should be formatted using PSR-1/PSR-2 standards.

## About

- Author: [Jodie Dunlop](https://github.com/jodiedunlop)
- License: [MIT](LICENSE)
- Copyright (c) 2018 Rex Software Pty Ltd
