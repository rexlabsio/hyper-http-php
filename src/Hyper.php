<?php

namespace Rexlabs\HyperHttp;

use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rexlabs\HyperHttp\Exceptions\BadConfigurationException;
use Rexlabs\HyperHttp\Message\CurlMessageFormatter;
use Rexlabs\HyperHttp\Message\Response;
use Rexlabs\UtilityBelt\ArrayUtility;

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
     * One instance per subclass.
     *
     * @var array|Client[]
     */
    protected static $instances = [];

    /**
     * Default logger used if not provided.
     *
     * @var null|LoggerInterface
     */
    protected static $defaultLogger;

    /**
     * Default config data, provided config is merged over.
     *
     * @var array
     */
    protected static $defaultConfig = [];

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
        $config = array_replace_recursive(self::$defaultConfig, $config);
        $guzzleConfig = $config['guzzle'] ?? [];
        $baseUri = static::getBaseUri() ?? $guzzleConfig['base_uri'] ?? null;
        unset($config['guzzle']);

        // If no logger explicitly provided use the default or null logger.
        if ($logger === null) {
            $logger = self::$defaultLogger ?? new NullLogger();
        }

        // We either get provided a guzzle client instance, or possibly an array
        // of configuration to manually setup a guzzle client.
        // But we can't have both.
        if ($guzzle !== null) {
            // Sanity check - may not provide both guzzle config and a guzzle client.
            // Since Guzzle is not configurable after initialisation.
            if (!empty($guzzleConfig)) {
                throw new BadConfigurationException('Cannot provide both guzzle client and config');
            }
            if ($baseUri !== null) {
                throw new BadConfigurationException('Cannot provide both guzzle client and base_uri');
            }
        } else {
            // If we don't have a guzzle client provided, then we'll setup a configuration
            // which includes logging middleware.
            if ($baseUri !== null) {
                $guzzleConfig['base_uri'] = $baseUri;
            }
            if (!isset($guzzleConfig['handler'])) {
                $guzzleConfig['handler'] = HandlerStack::create();
            }
            // Add curl request to log if requested
            $formatter = ArrayUtility::dotRead($config, 'log_curl', false)
                ? new CurlMessageFormatter()
                : null;
            $loggerMiddleware = new Logger($logger, $formatter);
            $loggerMiddleware->setRequestLoggingEnabled();
            $guzzleConfig['handler']->push($loggerMiddleware);
            $guzzle = new GuzzleClient(static::makeGuzzleConfig($guzzleConfig));
        }

        $client = static::makeClient($guzzle, $logger, static::makeConfig($config));

        if ($baseUri !== null) {
            $client->setBaseUri($baseUri);
        }

        return $client;
    }

    /**
     * @param array
     *
     * @return void
     */
    public static function setDefaultConfig(array $config)
    {
        self::$defaultConfig = $config;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public static function setDefaultLogger(LoggerInterface $logger)
    {
        self::$defaultLogger = $logger;
    }

    /**
     * Override to customise client class.
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
     * eg set default 'headers' to be merged onto every request.
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
     * Override to customise default guzzle client.
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
     * Override to provide a default base_uri to the client.
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

    /**
     * Clear saved instances from Hyper and subclasses.
     *
     * @return void
     */
    public static function clearInstances()
    {
        static::$instances = [];
    }
}
