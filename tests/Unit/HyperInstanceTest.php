<?php

namespace Rexlabs\HyperHttp\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Client;
use Rexlabs\HyperHttp\Exceptions\BadConfigurationException;
use Rexlabs\HyperHttp\Hyper;

/**
 * Class HyperInstanceTest
 *
 * @package Rexlabs\HyperHttp\Tests\Unit
 */
class HyperInstanceTest extends TestCase
{
    public function test_static_calls_share_instance(): void
    {
        $one = Hyper::instance();
        $two = Hyper::instance();
        $three = Hyper::make();

        $this->assertSame($one, $two);
        $this->assertNotSame($one, $three);
    }

    public function subclass_does_not_share_instance(): void
    {
        $newHyper = new class extends Hyper {};
        $one = Hyper::instance();
        $two = Hyper::instance();
        $three = $newHyper::instance();

        $this->assertSame($one, $two);
        $this->assertNotSame($one, $three);
    }

    public function test_instantiation_via_make(): void
    {
        $hyper = Hyper::make();
        $this->assertInstanceOf(Client::class, $hyper);
        $this->assertEquals([], $hyper->getConfig());
        $this->assertInstanceOf(GuzzleClient::class, $hyper->getGuzzleClient());
        $this->assertInstanceOf(LoggerInterface::class, $hyper->getLogger());
    }

    public function test_instantiation_via_make_with_guzzle_config(): void
    {
        $hyper = Hyper::make(['guzzle' => ['timeout' => 321]]);
        $this->assertContains(['timeout' => 321], $hyper->getGuzzleClient()->getConfig());
    }

    public function test_instantiation_via_make_cannot_provide_guzzle_client_and_config(): void
    {
        $this->expectException(BadConfigurationException::class);
        Hyper::make(
            ['guzzle' => ['timeout' => 321]],
            new GuzzleClient()
        );
    }

    public function test_instantiation_with_logger_assigns_logger_middleware(): void
    {
        $hyper = Hyper::make(['guzzle' => ['timeout' => 321]], null, new NullLogger());
        $this->assertContains([
            'timeout' => 321,
            'handler',
        ], $hyper->getGuzzleClient()->getConfig());
    }
}
