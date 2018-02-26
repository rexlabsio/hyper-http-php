<?php
namespace Rexlabs\HyperHttp\Tests\Unit;

use ArrayObject;
use Rexlabs\HyperHttp\Exceptions\RequestException;
use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Exceptions\ResponseException;
use Rexlabs\HyperHttp\Client;

class ClientTest extends TestCase
{
    public function test_constructor_without_arguments()
    {
        new Client();
    }

    public function test_url()
    {
        $api = Client::make();

        // No trailing slash on base
        $api->setBaseUri('http://example.com/v1');
        $this->assertEquals('http://example.com/v1/something', $api->url('something'));
        $this->assertEquals('http://example.com/v1/something', $api->url('/something'));

        // Trailing slash on base
        $api->setBaseUri('http://example.com/v1/');
        $this->assertEquals('http://example.com/v1/something', $api->url('something'));
        $this->assertEquals('http://example.com/v1/something', $api->url('/something'));

        // Absolute URL ignores base URL
        $api->setBaseUri('http://example.com/v1/ignore/me');
        $this->assertEquals('http://example.com/v2/something', $api->url('http://example.com/v2/something'));

        // Empty base URI
        $api->setBaseUri('');
        $this->assertEquals('/something', $api->url('/something'));
    }

    public function test_set_config_after_constructor()
    {
        $api = Client::make();
        $api->setConfig([
            'base_uri' => 'http://example.com/v1',
        ]);
        $this->assertArrayHasKey('base_uri', $api->getConfig());
        $this->assertEquals('http://example.com/v1', $api->getBaseUri());
    }

    public function test_guzzle_getter_creates_new_client()
    {
        $api = Client::make();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $api->getGuzzleClient());
    }

    public function test_constructor_accepts_guzzle_client()
    {
        $guzzle = new Client(['timeout' => 222]);
        $api = new Client([], $guzzle);
        $this->assertSame($guzzle, $api->getGuzzleClient());
        $this->assertEquals(222, $api->getGuzzleClient()->getConfig('timeout'));
    }

    public function test_constructor_accepts_logger()
    {
        $logger = new NullLogger();
        $api = new Client([], null, $logger);
        $this->assertSame($logger, $api->getLogger());
    }

    public function test_logger()
    {
        $api = Client::make();
        $api->getLogger()->log(LogLevel::ALERT, "Test Log");
    }

    public function test_set_base_uri()
    {
        $api = Client::make();
        $api->setBaseUri('http://example.com/v1');
        $this->assertEquals('http://example.com/v1', $api->getBaseUri());
    }

    public function test_fluent_initialisation()
    {
        $api = Client::make()
            ->setBaseUri('http://example.com/v1')
            ->setLogger(new NullLogger())
            ->setGuzzleClient(new Client());
        $this->assertEquals('http://example.com/v1', $api->getBaseUri());
        $this->assertInstanceOf(NullLogger::class, $api->getLogger());
        $this->assertInstanceOf(Client::class, $api->getGuzzleClient());
    }

    public function test_manipulate_headers()
    {
        $api = Client::make();
        $api->setHeader('X-App-Identity', 'MyApplication1234');
        $api->setHeader('X-Another-Header', 'Another Header');
        $this->assertEquals('MyApplication1234', $api->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $api->getHeader('X-Another-Header'));
        $this->assertEquals('54321', $api->setHeader('X-App-Identity', '54321')->getHeader('X-App-Identity'));

    }

    public function test_set_multiple_headers()
    {
        $api = Client::make()->setHeaders([
            'X-App-Identity' => 'MyApplication1234',
            'X-Another-Header' => 'Another Header'
        ]);
        $this->assertEquals('MyApplication1234', $api->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $api->getHeader('X-Another-Header'));
    }

    public function test_set_headers_via_constructor()
    {
        $api = Client::make([
            'headers' => [
                'X-App-Identity' => 'MyApplication1234',
                'X-Another-Header' => 'Another Header'
            ]
        ]);
        $this->assertEquals('MyApplication1234', $api->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $api->getHeader('X-Another-Header'));
    }

    public function test_get_request_with_valid_data_returns_data_object()
    {
        $guzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ]
        ]);
        $api = Client::make([], $guzzle);
        $result = $api->get('/message/12345');
        $this->assertInstanceOf(ArrayObject::class, $result);
        $this->assertEquals([
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ],
        ], $result->toArray());
    }

    public function test_get_request_with_sub_records()
    {
        $guzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
                'recipients' => [
                    [
                        'id' => 'r1',
                        'address' => 'bob@example.com',
                    ],
                    [
                        'id' => 'r2',
                        'address' => 'alice@example.com',
                    ]
                ]
            ]
        ]);
        $api = new Client([], $guzzle);
        $result = $api->get('/message/12345');
        $this->assertInstanceOf(DataObject::class, $result);

        $message = $result->data;
        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertInstanceOf(DataObject::class, $message->recipients);
        $this->assertTrue($message->recipients->isCollection());
        $this->assertCount(2, $message->recipients);
        $this->assertEquals('r2', $message->get('recipients.1.id'));
        $this->assertEquals(['bob@example.com', 'alice@example.com'], $message->recipients->pluck('address'));
    }

    public function test_invalid_http_status_results_in_exception()
    {
        $guzzle = $this->getMockedGuzzle(404, [
            'Content-Type' => 'application/json',
        ], null);

        $api = Client::make([], $guzzle);
        $this->expectException(ResponseException::class);
        $api->get('/message/1');

        $guzzle = $this->getMockedGuzzle(500, [
            'Content-Type' => 'application/json',
        ], null);

        $api = new Client([], $guzzle);
        $this->expectException(RequestException::class);
        $api->get('/message/2');

    }

    public function test_get_request_with_response_callback()
    {
        $guzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ]
        ]);
        $api = Client::make([], $guzzle);
        $api->onResponseData(function ($data) {
            return [
                'data' => [
                    'id' => $data['data']['id'] * 2,
                    'message' => strtoupper($data['data']['message']),
                ]
            ];
        });
        $result = $api->get('/message/12345');
        $this->assertEquals('HELLO', $result->data->message);
        $this->assertEquals(5678 * 2, $result->data->id);
    }

    public function test_get_request_with_raw_response_callback()
    {
        $guzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ]
        ]);
        $api = new Client([], $guzzle);
        $api->onRawResponseData(function ($data) {
            return str_replace('hello', 'bye', $data);
        });
        $result = $api->get('/message/12345');
        $this->assertEquals("bye", $result->data->message);
    }


    protected function getMockedGuzzle($statusCode = 200, array $headers = [], $payload = null)
    {
        // Create a mock and queue two responses.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response($statusCode, $headers, $payload !== null ? json_encode($payload) : $payload)
        ]);

        $handler = \GuzzleHttp\HandlerStack::create($mock);
        return new GuzzleClient(['handler' => $handler]);
    }
}