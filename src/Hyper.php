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
use Rexlabs\HyperHttp\Exceptions\RequestException;
use Rexlabs\HyperHttp\Exceptions\ResponseException;
use Rexlabs\HyperHttp\Message\Request;
use Rexlabs\HyperHttp\Message\Response;

/**
 * Hyper HTTP Client
 *
 * @method static Response call(string $method, string | UriInterface $uri, mixed $body = null, array $headers = [],
 *         array $options = [])
 * @method static Response get(string | UriInterface $uri, array $query = [], mixed | null $body = null, array $headers
 *         = [], array $options = [])
 * @method static Response post(string | UriInterface $uri, mixed | null $body = null, array $headers = [], array
 *         $options = [])
 * @method static Response put(string | UriInterface $uri, mixed | null $body = null, array $headers = [], array
 *         $options = [])
 * @method static Response patch(string | UriInterface $uri, mixed | null $body = null, array $headers = [], array
 *         $options = [])
 * @method static Response delete(string | UriInterface $uri, mixed | null $body = null, array $headers = [], array
 *         $options = [])
 *
 * @author        Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
 * @copyright (c) 2018 Rex Software Pty Ltd.
 * @license       MIT
 * @package       Rexlabs\HyperHttp
 *
 *
 */
class Hyper implements LoggerAwareInterface
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

    /** @var bool */
    protected $applyJsonHeaders;

    public function __construct(GuzzleClient $guzzle, LoggerInterface $logger, array $config = [])
    {
        $this->setGuzzleClient($guzzle);
        $this->setLogger($logger);
        $this->setConfig($config);
    }

    /**
     * Makes a new instance of the class, with appropriate defaults.
     * You can optionally pass in configuration options, a Guzzle client instance and/or a logger.
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
            $loggerMiddleware->setRequestLoggingEnabled();
            $guzzleConfig['handler']->push($loggerMiddleware);
        }

        return new static($guzzle ?? new GuzzleClient($guzzleConfig), $logger ?? new NullLogger, $config);
    }



    /**
     * Make a GET request and return a Response object.
     * @param string|UriInterface $uri
     * @param array               $query GET query options
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpGet($uri, array $query = [], $body = null, array $headers = [], array $options = []): Response
    {
        return $this->httpCall('GET', $this->makeUri($uri)->withQuery(http_build_query($query, null, '&')), $body,
            $headers, $options);
    }

    /**
     * Make a POST request and return a Response object.
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     */
    public function httpPost($uri, $body, array $headers = [], array $options = []): Response
    {
        // TODO: This sending json: automatically set headers?
        return $this->httpCall('POST', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a POST request with Form parameters and return a Response object.
     * @param string|UriInterface $uri
     * @param array               $params
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpPostForm($uri, array $params = [], array $headers = [], array $options = []): Response
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['form_params'] = $params;

        return $this->httpCall('POST', $uri, null, $headers, $options);
    }

    /**
     * Make a multipart POST request with Form parameters and return a Response object.
     * @param string|UriInterface $uri
     * @param array               $formParams
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpPostMultipartForm(
        $uri,
        array $formParams = [],
        array $headers = [],
        array $options = []
    ): Response {
        $headers['Content-Type'] = 'multipart/form-data';
        $options['multipart'] = $formParams;

        return $this->httpCall('POST', $uri, null, $headers, $options);
    }

    /**
     * Make a PUT request and return a Response object.
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpPut($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->httpCall('PUT', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a PATCH request and return a Response object.
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpPatch($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->httpCall('PATCH', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a DELETE request and return a Response object.
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function httpDelete($uri, $body = null, array $headers = [], array $options = []): Response
    {
        return $this->httpCall('DELETE', $this->makeUri($uri), $body ?? null, $headers, $options);

    }

    /**
     * Make a request (with given method) and return a Response object.
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed|null          $body
     * @param array               $headers
     * @param array               $options
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     */
    public function httpCall($method, $uri, $body = null, array $headers = [], array $options = []): Response
    {
        try {
            $request = $this->createRequest($method, $this->makeUri($uri), $headers, $body);
            $response = $this->httpSend($request, $options);

            // Note: Guzzle will only throw exceptions for status codes when http_errors = true
            // This option only has an effect if your handler has the GuzzleHttp\Middleware::httpErrors middleware
            // See: http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            throw new ResponseException($e->getMessage(), $e->getCode(), $e);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e);
        }

        return $response;
    }

    /**
     * Create a new request object
     * @param string $method
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param null   $version
     * @return Request
     */
    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = null
    ): Request {
        $headers = $this->mergeHeaders($headers ?? []);

        if (\is_array($body)) {
            $body = \GuzzleHttp\json_encode($body);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
        }

        if ($this->applyJsonHeaders) {
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
            if (!isset($headers['Accept-Type'])) {
                $headers['Accept-Type'] = 'application/json';
            }
        }

        $request = new \GuzzleHttp\Psr7\Request($this->sanitizeMethod($method), $this->makeUri($uri), $headers,
            $body ?? null, $version ?? 1.1);

        return Request::fromRequest($request);
    }

    /**
     * Send a Request object and get a Response
     * @param RequestInterface $request
     * @param array            $options
     * @return Response
     */
    public function httpSend(RequestInterface $request, array $options = []): Response
    {
        $this->getLogger()->debug(sprintf('Sending: %s %s', $request->getMethod(), $request->getUri()), $options);

        // Send the request and get a response object
        $response = Response::fromResponse($this->getGuzzleClient()->send($request, $options));
        $response->setRequest($request);

        return $response;
    }

    /**
     * Prepends the base URI to any non-absolute URI
     * @param string $uri
     * @return string
     */
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
     * Returns the Logger instance
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the Logger instance which will be used to log requests and responses.
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the underlying Guzzle client used to transport the requests.
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
        if (isset($config['headers']) && \is_array($config['headers'])) {
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
     * Replace the headers with the supplied array (key => value)
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

    /**
     * Log a message via the logger
     * @param       $level
     * @param       $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        $this->getLogger()->log($level, $message, $context);
    }

    public function usingJson(bool $enabled = true)
    {
        $instance = $this;

        if ($this->applyJsonHeaders !== $enabled) {
            $instance = clone $this;
            $instance->applyJsonHeaders = $enabled;
        }

        return $instance;
    }

    /**
     * Routes missing object methods to http{MethodName}.
     * This allows get(), put(), patch() etc. to be aliased to httpGet() httpPut() httpPatch() ...
     * @param $name
     * @param $arguments
     * @return Response
     * @throws \Rexlabs\HyperHttp\Exceptions\ResponseException
     * @throws \Rexlabs\HyperHttp\Exceptions\RequestException
     */
    public function __call($name, $arguments)
    {
        $instance = $this;

        // When the method call is suffixed with Json we will force json
        // headers by
        if (preg_match('/json$/i', $name)) {
            $name = preg_replace('/json$/i', '', $name);
            $instance = $this->usingJson();  // Get a cloned object
        }
        $httpMethod = 'http' . ucfirst($name);
        if (method_exists($instance, $httpMethod)) {
            // Call http method
            return $instance->$httpMethod(...$arguments);
        }

        return $instance->httpCall($instance->sanitizeMethod($name), ...$arguments);
    }

    /**
     * Route static calls to an instance of the class.
     * Makes it possible to call class::get() etc. without making an instance first.
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

    /**
     * Merges the given headers with the global headers stored in the instance.
     * @param array $headers
     * @return array
     */
    protected function mergeHeaders(array $headers): array
    {
        return array_merge_recursive($headers, $this->headers);
    }

    /**
     * Sanitizes an HTTP method
     * @param $method
     * @return string
     */
    protected function sanitizeMethod(string $method): string
    {
        return strtoupper(trim($method));
    }

    /**
     * @param string|UriInterface $uri
     * @return Uri|UriInterface
     */
    protected function makeUri($uri): Uri
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        return new Uri($this->url($uri));
    }
}