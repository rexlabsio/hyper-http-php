<?php

namespace Rexlabs\HyperHttp\Tests\Unit\Logging;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Hyper;

/**
 * Class LogFormatTest.
 */
class LogFormatTest extends TestCase
{
    protected function setUp()
    {
        Hyper::setDefaultConfig([]);
        Hyper::clearInstances();
        Hyper::setDefaultLogger(new NullLogger());
    }

    /**
     * Get a testable dummy logger.
     *
     * @return mixed
     */
    private function getTestLogger()
    {
        return new class() extends AbstractLogger {
            public $messages = [];

            public function log($level, $message, array $context = [])
            {
                $this->messages[] = $message;
            }
        };
    }

    public function test_request_logs()
    {
        $code = 205;
        $url = 'https://www.not-a-real-domain.com';
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response($code, ['X-Foo' => 'Bar']),
        ]));
        $config = [
            'guzzle' => [
                'handler' => $handlerStack,
            ],
        ];
        $logger = $this->getTestLogger();
        $client = Hyper::make($config, null, $logger);
        $response = $client->post($url, ['foo' => 'bar']);

        $this->assertEquals($code, $response->getStatusCode());
        $this->assertNotEmpty($logger->messages);
        $this->assertStringStartsNotWith('curl', $logger->messages[0]);
        $this->assertContains((string) $code, $logger->messages[1]);
    }

    public function test_log_curl()
    {
        $code = 205;
        $url = 'https://www.not-a-real-domain.com';
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response($code, ['X-Foo' => 'Bar']),
        ]));
        $config = [
            'log_curl' => true,
            'guzzle'   => [
                'handler' => $handlerStack,
            ],
        ];
        $logger = $this->getTestLogger();
        $client = Hyper::make($config, null, $logger);
        $response = $client->post($url, ['foo' => 'bar']);

        $this->assertEquals($code, $response->getStatusCode());
        $this->assertNotEmpty($logger->messages);
        $this->assertStringStartsWith('curl', $logger->messages[0]);
        $this->assertContains((string) $code, $logger->messages[1]);
    }
}
