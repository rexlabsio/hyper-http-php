<?php
namespace Rexlabs\HyperHttp;

use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;
use Rexlabs\ArrayObject\ArrayObject;
use Rexlabs\HyperHttp\Exceptions\RequestException;
use Rexlabs\HyperHttp\Exceptions\ResponseException;
use Rexlabs\HyperHttp\Message\Request;
use Rexlabs\HyperHttp\Message\Response;

/**
 * Hyper HTTP Client
 * @author        Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
 * @copyright (c) 2018 Rex Software Pty Ltd.
 * @license       MIT
 * @mixin         \Rexlabs\ArrayObject\ArrayObject
 * @package       Rexlabs\HyperHttp
 */
class Client implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var array */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

    /** @var GuzzleClient */
    protected $guzzle;

    /** @var string|null */
    protected $baseUri;

    /** @var array */
    protected $headers = [];

    /** @var callable */
    protected $rawResponseDataCallback;

    /** @var callable */
    protected $responseDataCallback;

    public function __construct(array $config = [], GuzzleClient $guzzle, LoggerInterface $logger)
    {
        $this->setConfig($config);
        $this->setGuzzleClient($guzzle);
        $this->setLogger($logger);
    }

    /**
     * @param array                $config
     * @param GuzzleClient|null    $guzzle
     * @param LoggerInterface|null $logger
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function make(array $config = [], GuzzleClient $guzzle = null, LoggerInterface $logger = null)
    {
        $guzzleConfig = $config['guzzle'] ?? [];
        unset($config['guzzle']);

        // May not provide both guzzle config and a guzzle client.
        // Since Guzzle is not configurable after initialisation.
        if (!empty($guzzleConfig) && $guzzle !== null) {
            throw new \InvalidArgumentException('Cannot provide both guzzle client and config');
        }

        // Setup logging middleware when a logger is passed.
        if ($guzzle === null && $logger !== null) {
            if (!isset($guzzleConfig['handler'])) {
                $guzzleConfig['handler'] = HandlerStack::create();
            }
            $loggerMiddleware = new Logger($logger);
            $loggerMiddleware->setRequestLoggingEnabled(true);
            $guzzleConfig['handler']->push($loggerMiddleware);
        }

        return new static($config, $guzzle ?? new GuzzleClient($guzzleConfig), $logger ?? new NullLogger);
    }

    /**
     * @param string|UriInterface $uri
     * @param array               $query GET query options
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function get($uri, array $query = [], $body = null, array $headers = [], array $options = [])
    {
        return $this->call('GET', $this->makeUri($uri)->withQuery(http_build_query($query, null, '&')), $body, $headers,
            $options);
    }

    /**
     * Post JSON
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function post($uri, $body, array $headers = [], array $options = [])
    {
        // TODO: This sending json: automatically set headers?
        return $this->call('POST', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array               $params
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function postForm($uri, array $params = [], array $headers = [], array $options = [])
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['form_params'] = $params;

        return $this->call('POST', $uri, null, $headers, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array               $formParams
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function postMultipartForm($uri, array $formParams = [], array $headers = [], array $options = [])
    {
        $headers['Content-Type'] = 'multipart/form-data';
        $options['multipart'] = $formParams;

        return $this->call('POST', $uri, null, $headers, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function put($uri, $body, array $headers = [], array $options = [])
    {
        return $this->call('PUT', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function patch($uri, $body, array $headers = [], array $options = [])
    {
        return $this->call('PATCH', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return ArrayObject|mixed
     */
    public function delete($uri, $body = null, array $headers = [], array $options = [])
    {
        $options['body'] = is_array($body) ? json_encode($body) : $body;

        return $this->call('DELETE', $this->makeUri($uri), $headers, $body ?? null, $options);

    }

    /**
     * Make a GET request and return an object from the JSON response
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function call($method, $uri, $body = null, array $headers = [], array $options = [])
    {
        try {
            $request = $this->createRequest($method, $this->makeUri($uri), $headers, $body);
            $response = $this->sendRequest($request, $options);

            // Note: Guzzle will only throw exceptions for status codes when http_errors = true
            // This option only has an effect if your handler has the GuzzleHttp\Middleware::httpErrors middleware
            // See: http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            throw new ResponseException($e->getMessage(), $e->getCode(), $e);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e);
        }

        // TODO: Can we switch the response to our own??

        // Provide an opportunity for a callback to massage the RAW response body
//            $body = $this->fireRawResponseDataCallback($body);
//            $body = $this->fireResponseDataCallback($body);

        return $response;
    }

    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = null
    ): Request {
        $headers = $this->combineHeaders($headers ?? []);

        // TODO: if ($this->wantsJson) { ...
        // Supplement headers for JSON
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }
        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }

        $request = new \GuzzleHttp\Psr7\Request($this->sanitizeMethod($method), $this->makeUri($uri), $headers,
            $body ?? null, $version ?? 1.1);

        return Request::fromRequest($request);
    }

    public function sendRequest(RequestInterface $request, array $options = []): Response
    {
        $this->getLogger()->debug(sprintf('Sending: %s %s', $request->getMethod(), $request->getUri()), $options);

        // Send the request and get a response object
        $response = Response::fromResponse($this->getGuzzleClient()->send($request, $options));
        $response->setRequest($request);

        return $response;
    }


    public function url($uri)
    {
        $url = $uri;

        if (!preg_match('#^https?://#', $uri)) {
            $url = strpos($uri, '/') !== 0 ? "/$uri" : $uri;
            if ($this->baseUri) {
                $url = preg_replace('#/$#', '', $this->baseUri) . $url;
            }
        }

        return $url;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return GuzzleClient
     */
    public function getGuzzleClient(): GuzzleClient
    {
        return $this->guzzle;
    }

    /**
     * @param GuzzleClient $guzzle
     * @return $this
     */
    public function setGuzzleClient(GuzzleClient $guzzle)
    {
        $this->guzzle = $guzzle;

        return $this;
    }


    /**
     * Get the last response
     * @return Response
     */
    public function getResponse(): \GuzzleHttp\Psr7\Response
    {
        return $this->response;
    }

    /**
     * Return the HTTP status code from the last response
     * @return int|null
     */
    public function getStatusCode()
    {
        return isset($this->response) ? $this->response->getStatusCode() : null;
    }

    /**
     * Return the HTTP status reason phrase from the last response
     * @return null|string
     */
    public function getReasonPhrase()
    {
        return isset($this->response) ? $this->response->getReasonPhrase() : null;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        // Base URI may be set from config
        if (isset($config['base_uri'])) {
            $this->setBaseUri($config['base_uri']);
        }

        // Config may also include headers
        if (isset($config['headers']) && is_array($config['headers'])) {
            $this->setHeaders($config['headers']);
            unset($config['headers']);
        }

        $this->config = $config;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBaseUri()
    {
        return $this->baseUri ?? null;
    }

    /**
     * Convenience method for configuring the base URI.
     * @param string $uri
     * @return $this
     */
    public function setBaseUri($uri)
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * Get a header by key
     * @param string      $key
     * @param string|null $default
     * @return string|null
     */
    public function getHeader($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Set/replace a header by key
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = (string)$value;

        return $this;
    }

    /**
     * Set (REPLACE) an array (key => value) of headers
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Create a new instance of the client with additional headers set
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return (clone $this)->addHeaders($headers);
    }

    /**
     * Merge a new set of headers with any existing headers.  (Existing keys are overwritten).
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers ?? [], $headers);

        return $this;
    }

    public function log($level, $message, array $context = [])
    {
        $this->getLogger()->log($level, $message, $context);
    }

    protected function combineHeaders($headers)
    {
        return array_merge_recursive($headers, $this->headers);
    }

    protected function sanitizeMethod($method)
    {
        return strtoupper(trim($method));
    }

    protected function makeUri($uri): Uri
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        return new Uri($this->url($uri));
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function __callStatic($name, $arguments)
    {
        $client = static::make();
        return \call_user_func_array([$client, $name], $arguments);
    }
}