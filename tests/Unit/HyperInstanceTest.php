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
 * Class HyperInstanceTest.
 */
class HyperInstanceTest extends TestCase
{
    protected function setUp()
    {
        Hyper::setDefaultConfig([]);
        Hyper::clearInstances();
        Hyper::setDefaultLogger(new NullLogger());
    }

    public function test_static_calls_share_instance()
    {
        $one = Hyper::instance();
        $two = Hyper::instance();
        $three = Hyper::make();

        $this->assertSame($one, $two);
        $this->assertNotSame($one, $three);
    }

    public function test_subclass_does_not_share_instance()
    {
        $newHyper = new class() extends Hyper {
        };
        $one = Hyper::instance();
        $two = Hyper::instance();
        $three = $newHyper::instance();

        $this->assertSame($one, $two);
        $this->assertNotSame($one, $three);
    }

    public function test_clears_instances()
    {
        $newHyper = new class() extends Hyper {
        };
        $hyperOne = Hyper::instance();
        $hyperTwo = Hyper::instance();

        $newHyperOne = $newHyper::instance();
        $newHyperTwo = $newHyper::instance();

        $this->assertSame($hyperOne, $hyperTwo);
        $this->assertSame($newHyperOne, $newHyperTwo);
        $this->assertNotSame($hyperOne, $newHyperOne);

        Hyper::clearInstances();

        $hyperThree = Hyper::instance();
        $hyperFour = Hyper::instance();

        $newHyperThree = $newHyper::instance();
        $newHyperFour = $newHyper::instance();

        $this->assertSame($hyperThree, $hyperFour);
        $this->assertSame($newHyperThree, $newHyperFour);
        $this->assertNotSame($hyperThree, $newHyperThree);
        $this->assertNotSame($hyperOne, $hyperThree);
        $this->assertNotSame($newHyperOne, $newHyperThree);
    }

    public function test_instantiation_via_make()
    {
        $hyper = Hyper::make();
        $this->assertInstanceOf(Client::class, $hyper);
        $this->assertEquals([], $hyper->getConfig());
        $this->assertInstanceOf(GuzzleClient::class, $hyper->getGuzzleClient());
        $this->assertInstanceOf(LoggerInterface::class, $hyper->getLogger());
    }

    public function test_instantiation_via_make_with_guzzle_config()
    {
        $hyper = Hyper::make(['guzzle' => ['timeout' => 321]]);
        $this->assertContains(['timeout' => 321], $hyper->getGuzzleClient()->getConfig());
    }

    public function test_instantiation_via_make_cannot_provide_guzzle_client_and_config()
    {
        $this->expectException(BadConfigurationException::class);
        Hyper::make(
            ['guzzle' => ['timeout' => 321]],
            new GuzzleClient()
        );
    }

    public function test_instantiation_with_logger_assigns_logger_middleware()
    {
        $hyper = Hyper::make(['guzzle' => ['timeout' => 321]], null, new NullLogger());
        $this->assertContains([
            'timeout' => 321,
            'handler',
        ], $hyper->getGuzzleClient()->getConfig());
    }
}
