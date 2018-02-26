# Hyper Http Client

## Overview

Hyper is an HTTP Client that aims to provide a simple, but powerful interface for making HTTP calls and fetching and manipulating API data.

## Example Usage

The RESTful methods all return a `Response` object which makes interacting with responses simple.

```php
use Rexlabs\HyperHttp\Client;

$hyper = Client::make();

// Optionally setup a base URI, and headers to be used for all requests
$hyperClient->setBaseUri('http://example.com/api/v1')
    ->setHeader('X-App-Identity', 'Some App')
    ->setHeader('X-Correlation-Id', '12345')
    ->setLogger(new \Rexlabs\Logger\Logger);

// Make a request to the 'http://example.com/api/v1/messages' endpoint
$response = $hyper->get('/messages');

// The Response object mixes in the Rexlabs\ArrayObject class to provide flient access
// to JSON responses
// See: rexlabs\array-object for more details

$response->title;   // Get the "title" property from the top level response
$response->get('books.0');  // ArrayObject - the first book in the "books" collection


```

Based on the service returning the following JSON data:
```json
{
  "data": [
    {
      "id": 1,
      "subject": "hello"
    },
    {
      "id": 2,
      "subject": "bye"
    }
  ]
}
```


## Methods

The API client provides the following methods for interacting with remote endpoints:

### Request Methods

* `$response = $hyper->post($uri, $data, $headers, $options);`
* `$response = $hyper->postForm($uri, $formParams, $headers, $options);`
* `$hyper->put($uri, $data, $headers, $options);`
* `$hyper->patch($uri, $data, $headers, $options);`
* `$hyper->delete($uri, $data, $headers, $options);`

`$options` is optional, and can be used to override Guzzle options

## Dependencies

To install dependencies run:
`composer install`

- PHP 7
- GuzzleHttp/Guzzle
- Rexlabs/ArrayObject
- psr/log

## Tests

To run tests:
`vendor/bin/phpunit`


