<?php

namespace Rexlabs\HyperHttp\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Rexlabs\HyperHttp\Hyper;

/**
 * Class ConfigTest.
 */
class ConfigTest extends TestCase
{
    public function test_default_config_used()
    {
        $logCurl = [
            'log_curl' => true,
        ];

        Hyper::setDefaultConfig($logCurl);
        $client = Hyper::make();
        $config = $client->getConfig();

        $this->assertEquals($logCurl, $config);
    }

    public function test_provided_config_overrides_default()
    {
        $logCurl = [
            'log_curl' => true,
        ];
        $dontLogCurl = [
            'log_curl' => false,
        ];

        Hyper::setDefaultConfig($logCurl);
        $client = Hyper::make($dontLogCurl);
        $config = $client->getConfig();

        $this->assertEquals($dontLogCurl, $config);
    }

    public function test_nested_config_overrides_default()
    {
        $defaultConfig = [
            'log_curl' => true,
            'one'      => 'one',
            'nested'   => [
                'verify' => false,
            ],
        ];
        $overrideConfig = [
            'log_curl' => false,
            'two'      => 'two',
            'nested'   => [
                'verify' => true,
            ],
        ];
        $mergedConfig = [
            'log_curl' => false,
            'one'      => 'one',
            'two'      => 'two',
            'nested'   => [
                'verify' => true,
            ],
        ];

        Hyper::setDefaultConfig($defaultConfig);
        $client = Hyper::make($overrideConfig);
        $config = $client->getConfig();

        $this->assertEquals($mergedConfig, $config);
    }
}
