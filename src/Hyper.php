<?php

namespace Rexlabs\HyperHttp;

use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Exceptions\BadConfigurationException;
use Rexlabs\HyperHttp\Message\Response;

/**
 * Static factory/wrapper for the Hyper Client.
 *
 * @author        Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
 * @copyright (c) 2018 Rex Software Pty Ltd.
 * @license       MIT
 */
class Hyper
{
    /**
     * One instance per subclass
     *
     * @var array|Client[]
     */
    protected static $instances = [];

    /**
     * Override to use subclasses of Client
     *
     * @var string
     */
    protected static $clientClass = Client::class;

    /**
     * Makes a new instance of the Client class, with appropriate defaults.
     * You can optionally pass in configuration options, a Guzzle client instance and/or a logger.
     *
     * @param array                $config
     * @param GuzzleClient|null    $guzzle
     * @param LoggerInterface|null $logger
     *
     * @throws BadConfigurationException
     *
     * @return Client
     */
    public static function make(array $config = [], GuzzleClient $guzzle = null, LoggerInterface $logger = null): Client
    {
        $guzzleConfig = $config['guzzle'] ?? [];
        unset($config['guzzle']);

        // May not provide both guzzle config and a guzzle client.
        // Since Guzzle is not configurable after initialisation.
        if (!empty($guzzleConfig) && $guzzle !== null) {
            throw new BadConfigurationException('Cannot provide both guzzle client and config');
        }

        $baseUri = static::getBaseUri();

        // May not provide both base_uri and a guzzle client.
        // Since Guzzle is not configurable after initialisation.
        if ($baseUri !== null && $guzzle !== null) {
            throw new BadConfigurationException('Cannot provide both guzzle client and base_uri');
        }

        // Set base_uri on new guzzle client
        if ($baseUri !== null) {
            $guzzleConfig['base_uri'] = $baseUri;
        }

        // Setup logging middleware when a logger is passed.
        if ($guzzle === null && $logger !== null) {
            if (!isset($guzzleConfig['handler'])) {
                $guzzleConfig['handler'] = HandlerStack::create();
            }
            $loggerMiddleware = new Logger($logger);
            $loggerMiddleware->setRequestLoggingEnabled();
            $guzzleConfig['handler']->push($loggerMiddleware);
        }

        return static::makeClient(
            $guzzle ?? new GuzzleClient(static::makeGuzzleConfig($guzzleConfig)),
            $logger ?? new NullLogger(),
            static::makeConfig($config)
        );
    }

    /**
     * Override to customise client class
     *
     * @param GuzzleClient    $guzzle
     * @param LoggerInterface $logger
     * @param array           $config
     *
     * @return Client
     */
    protected static function makeClient(
        GuzzleClient $guzzle,
        LoggerInterface $logger,
        array $config
    ): Client {
        return new Client($guzzle, $logger, $config);
    }

    /**
     * Override to customise default client config
     * eg set default 'headers' to be merged onto every request
     *
     * @param array $config
     *
     * @return array
     */
    protected static function makeConfig(array $config): array
    {
        return $config;
    }

    /**
     * Override to customise default guzzle client
     *
     * @param array $config
     *
     * @return array
     */
    protected static function makeGuzzleConfig(array $config): array
    {
        return $config;
    }

    /**
     * Override to provide a default base_uri to the client
     *
     * @return null|string
     */
    protected static function getBaseUri()
    {
        return null;
    }

    /**
     * Re-uses an existing instance, or creates a new instance (via make) of the Client class.
     *
     * @param array                $config
     * @param GuzzleClient|null    $guzzle
     * @param LoggerInterface|null $logger
     *
     * @return Client
     */
    public static function instance(array $config = [], GuzzleClient $guzzle = null, LoggerInterface $logger = null): Client
    {
        if (!array_key_exists(static::class, self::$instances)) {
            static::$instances[static::class] = static::make($config, $guzzle, $logger);
        }

        return static::$instances[static::class];
    }

    /**
     * Make a GET request and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param array               $query   GET query options
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function get($uri, array $query = [], $body = null, array $headers = [], array $options = []): Response
    {
        return static::instance()->get($uri, $query, $body, $headers, $options);
    }

    /**
     * Make a POST request and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function post($uri, $body, array $headers = [], array $options = []): Response
    {
        return static::instance()->post($uri, $body, $headers, $options);
    }

    /**
     * Make a POST request with Form parameters and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param array               $params
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function postForm($uri, array $params = [], array $headers = [], array $options = []): Response
    {
        return static::instance()->postForm($uri, $params, $headers, $options);
    }

    /**
     * Make a multipart POST request with Form parameters and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param array               $formParams
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function postMultipartForm(
        $uri,
        array $formParams = [],
        array $headers = [],
        array $options = []
    ): Response {
        return static::instance()->postMultipartForm($uri, $formParams, $headers, $options);
    }

    /**
     * Make a PUT request and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function put($uri, $body, array $headers = [], array $options = []): Response
    {
        return static::instance()->put($uri, $body, $headers, $options);
    }

    /**
     * Make a PATCH request and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function patch($uri, $body, array $headers = [], array $options = []): Response
    {
        return static::instance()->patch($uri, $body, $headers, $options);
    }

    /**
     * Make a DELETE request and return a Response object.
     *
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     *
     * @return Response
     */
    public static function delete($uri, $body = null, array $headers = [], array $options = []): Response
    {
        return static::instance()->delete($uri, $body, $headers, $options);
    }

    /**
     * Make a request (with given method) and return a Response object.
     *
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     *
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     *
     * @return Response
     */
    public static function call($method, $uri, $body = null, array $headers = [], array $options = []): Response
    {
        return static::instance()->call($method, $uri, $body, $headers, $options);
    }
}
