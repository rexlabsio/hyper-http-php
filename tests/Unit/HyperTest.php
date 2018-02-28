<?php
namespace Rexlabs\HyperHttp\Tests\Unit;

use Psr\Http\Message\UriInterface;
use Rexlabs\ArrayObject\ArrayObject;
use Rexlabs\HyperHttp\Exceptions\RequestException;
use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Exceptions\ResponseException;
use Rexlabs\HyperHttp\Hyper;
use Rexlabs\HyperHttp\Message\Request;
use Rexlabs\HyperHttp\Message\Response;

class HyperTest extends TestCase
{
    public function test_url()
    {
        $hyper = Hyper::make();

        // No trailing slash on base
        $hyper->setBaseUri('http://example.com/v1');
        $this->assertEquals('http://example.com/v1/something', $hyper->url('something'));
        $this->assertEquals('http://example.com/v1/something', $hyper->url('/something'));

        // Trailing slash on base
        $hyper->setBaseUri('http://example.com/v1/');
        $this->assertEquals('http://example.com/v1/something', $hyper->url('something'));
        $this->assertEquals('http://example.com/v1/something', $hyper->url('/something'));

        // Absolute URL ignores base URL
        $hyper->setBaseUri('http://example.com/v1/ignore/me');
        $this->assertEquals('http://example.com/v2/something', $hyper->url('http://example.com/v2/something'));

        // Empty base URI
        $hyper->setBaseUri('');
        $this->assertEquals('/something', $hyper->url('/something'));
    }

    public function test_set_config_after_constructor()
    {
        $hyper = Hyper::make();
        $hyper->setConfig([
            'base_uri' => 'http://example.com/v1',
        ]);
        $this->assertArrayHasKey('base_uri', $hyper->getConfig());
        $this->assertEquals('http://example.com/v1', $hyper->getBaseUri());
    }

    public function test_guzzle_getter_creates_new_client()
    {
        $hyper = Hyper::make();
        $this->assertInstanceOf(GuzzleClient::class, $hyper->getGuzzleClient());
    }

    public function test_constructor_accepts_guzzle_client()
    {
        $mockedGuzzle = new GuzzleClient(['timeout' => 222]);
        $hyper = new Hyper($mockedGuzzle, new NullLogger());
        $this->assertSame($mockedGuzzle, $hyper->getGuzzleClient());
        $this->assertEquals(222, $hyper->getGuzzleClient()->getConfig('timeout'));
    }

    public function test_constructor_accepts_logger()
    {
        $logger = new NullLogger();
        $hyper = new Hyper($this->getMockedGuzzle(), $logger);
        $this->assertSame($logger, $hyper->getLogger());
    }

    public function test_logger()
    {
        $hyper = Hyper::make();
        $this->assertInstanceOf(NullLogger::class, $hyper->getLogger());
        $hyper->log(LogLevel::ALERT, 'Test Log');
    }

    public function test_set_base_uri()
    {
        $hyper = Hyper::make();
        $hyper->setBaseUri('http://example.com/v1');
        $this->assertEquals('http://example.com/v1', $hyper->getBaseUri());
    }

    public function test_fluent_initialisation()
    {
        $hyper = Hyper::make()
            ->setBaseUri('http://example.com/v1')
            ->setLogger(new NullLogger())
            ->setGuzzleClient(new GuzzleClient());
        $this->assertEquals('http://example.com/v1', $hyper->getBaseUri());
        $this->assertInstanceOf(NullLogger::class, $hyper->getLogger());
        $this->assertInstanceOf(GuzzleClient::class, $hyper->getGuzzleClient());
    }

    public function test_manipulate_headers()
    {
        $hyper = Hyper::make();
        $hyper->setHeader('X-App-Identity', 'MyApplication1234');
        $hyper->setHeader('X-Another-Header', 'Another Header');
        $this->assertEquals('MyApplication1234', $hyper->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $hyper->getHeader('X-Another-Header'));
        $this->assertEquals('54321', $hyper->setHeader('X-App-Identity', '54321')->getHeader('X-App-Identity'));

    }

    public function test_set_multiple_headers()
    {
        $hyper = Hyper::make()->setHeaders([
            'X-App-Identity' => 'MyApplication1234',
            'X-Another-Header' => 'Another Header'
        ]);
        $this->assertEquals('MyApplication1234', $hyper->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $hyper->getHeader('X-Another-Header'));
    }

    public function test_set_headers_via_constructor()
    {
        $hyper = Hyper::make([
            'headers' => [
                'X-App-Identity' => 'MyApplication1234',
                'X-Another-Header' => 'Another Header'
            ]
        ]);
        $this->assertEquals('MyApplication1234', $hyper->getHeader('X-App-Identity'));
        $this->assertEquals('Another Header', $hyper->getHeader('X-Another-Header'));
    }



    public function test_create_post_request()
    {
        $hyper = Hyper::make();
        $request = $hyper->createRequest('POST', '/fruit', [ 'X-Oranges' => 'No'], 'apples');
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals('/fruit', (string)$request->getUri());
        $this->assertEquals('', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('', $request->getHeaderLine('Accept-Type'));
        $this->assertEquals('No', $request->getHeaderLine('X-Oranges'));
        $this->assertEquals('apples', (string)$request->getBody());
    }

    public function test_json_magic_methods_set_json_headers()
    {
        $mockedGuzzle = $this->getMockedGuzzle(200, ['Content-Type' => 'application/json'], [], 5);
        $hyper = Hyper::make([], $mockedGuzzle);

        $response = $hyper->getJson('/some/uri');
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Accept-Type'));

        $response = $hyper->postJson('/some/uri', ['fruit' => 'apples']);
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Accept-Type'));

        $response = $hyper->putJson('/some/uri', ['fruit' => 'apples']);
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Accept-Type'));

        $response = $hyper->patchJson('/some/uri', ['fruit' => 'apples']);
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Accept-Type'));

        $response = $hyper->deleteJson('/some/uri');
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $response->getRequest()->getHeaderLine('Accept-Type'));

    }

    public function test_valid_get_request_returns_response()
    {
        $mockedGuzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ]
        ]);
        $hyper = Hyper::make([], $mockedGuzzle);
        $response = $hyper->httpGet('/message/12345');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals([
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ],
        ], $response->toArray());
    }

    public function test_get_request_with_sub_records()
    {
        $mockedGuzzle = $this->getMockedGuzzle(200, [
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
        $hyper = new Hyper($mockedGuzzle, new NullLogger());
        $response = $hyper->httpGet('/message/12345');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->has('data'));
        $message = $response->data;
        $this->assertInstanceOf(ArrayObject::class, $message);
        $this->assertTrue($message->has('recipients'));
        $this->assertInstanceOf(ArrayObject::class, $message->recipients);
        $this->assertTrue($message->recipients->isCollection());
        $this->assertCount(2, $message->recipients);
        $this->assertEquals('r2', $message->get('recipients.1.id'));
        $this->assertEquals(['bob@example.com', 'alice@example.com'], $message->recipients->pluckArray('address'));
    }

    public function test_invalid_http_status_results_in_exception()
    {
        $mockedGuzzle = $this->getMockedGuzzle(404, [
            'Content-Type' => 'application/json',
        ]);

        $hyper = Hyper::make([], $mockedGuzzle);
        $this->expectException(ResponseException::class);
        $hyper->httpGet('/message/1');

        $mockedGuzzle = $this->getMockedGuzzle(500, [
            'Content-Type' => 'application/json',
        ], null);

        $hyper = Hyper::make([], $mockedGuzzle);
        $this->expectException(RequestException::class);
        $hyper->httpGet('/message/2');

    }

    public function test_get_via_magic_method()
    {
        $mockedGuzzle = $this->getMockedGuzzle(200, [
            'Content-Type' => 'application/json',
        ], [
            'data' => [
                'id' => 5678,
                'message' => 'hello',
            ]
        ]);
        $hyper = Hyper::make([], $mockedGuzzle);
        $response = $hyper->get('/message/12345');
        $this->assertEquals('hello', $response->data->message);
        $this->assertEquals(5678, $response->data->id);
    }

    protected function getMockedGuzzle($statusCode = 200, array $headers = [], $payload = null, $count = 1)
    {
        $queue = [];
        for ($i = 0; $i < $count; $i++) {
            $queue[] = new \GuzzleHttp\Psr7\Response($statusCode, $headers, $payload !== null ? json_encode($payload) : $payload);
        }
        $handler = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler($queue));

        return new GuzzleClient(['handler' => $handler]);
    }
}