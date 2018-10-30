<?php

namespace Rexlabs\HyperHttp;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Rexlabs\HyperHttp\Exceptions\RequestException;
use Rexlabs\HyperHttp\Exceptions\ResponseException;
use Rexlabs\HyperHttp\Message\Request;
use Rexlabs\HyperHttp\Message\Response;

/**
 * Hyper HTTP Client.
 *
 * @author        Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
 * @copyright (c) 2018 Rex Software Pty Ltd.
 * @license       MIT
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

    /** @var bool */
    protected $applyJsonHeaders;

    public function __construct(GuzzleClient $guzzle, LoggerInterface $logger, array $config = [])
    {
        $this->setGuzzleClient($guzzle);
        $this->setLogger($logger);
        $this->setConfig($config);
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
    public function get($uri, array $query = [], $body = null, array $headers = [], array $options = []): Response
    {
        return $this->call('GET', $this->makeUri($uri)->withQuery(http_build_query($query, null, '&')), $body,
            $headers, $options);
    }

    /**
     * Make a GET request (with json headers) and return a Response object.
     *
     * @param       $uri
     * @param array $query
     * @param null  $body
     * @param array $headers
     * @param array $options
     *
     * @return Response
     */
    public function getJson($uri, array $query = [], $body = null, array $headers = [], array $options = []): Response
    {
        return $this->usingJson()->get($uri, $query, $body, $headers, $options);
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
    public function post($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->call('POST', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a POST request (with json headers) and return a Response object.
     *
     * @param       $uri
     * @param       $body
     * @param array $headers
     * @param array $options
     *
     * @return Response
     */
    public function postJson($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->usingJson()->post($uri, $body, $headers, $options);
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
    public function postForm($uri, array $params = [], array $headers = [], array $options = []): Response
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['form_params'] = $params;

        return $this->call('POST', $uri, null, $headers, $options);
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
    public function postMultipartForm(
        $uri,
        array $formParams = [],
        array $headers = [],
        array $options = []
    ): Response {
        $headers['Content-Type'] = 'multipart/form-data';
        $options['multipart'] = $formParams;

        return $this->call('POST', $uri, null, $headers, $options);
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
    public function put($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->call('PUT', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a PUT request (with json headers) and return a Response object.
     *
     * @param       $uri
     * @param       $body
     * @param array $headers
     * @param array $options
     *
     * @return Response
     */
    public function putJson($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->usingJson()->put($uri, $body, $headers, $options);
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
    public function patch($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->call('PATCH', $uri, \is_array($body) ? json_encode($body) : $body, $headers, $options);
    }

    /**
     * Make a PATCH request (with json headers) and return a Response object.
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
    public function patchJson($uri, $body, array $headers = [], array $options = []): Response
    {
        return $this->usingJson()->patch($uri, $body, $headers, $options);
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
    public function delete($uri, $body = null, array $headers = [], array $options = []): Response
    {
        return $this->call('DELETE', $this->makeUri($uri), $body ?? null, $headers, $options);
    }

    /**
     * Make a DELETE request (with json headers) and return a Response object.
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
    public function deleteJson($uri, $body = null, array $headers = [], array $options = []): Response
    {
        return $this->usingJson()->delete($uri, $body, $headers, $options);
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
    public function call($method, $uri, $body = null, array $headers = [], array $options = []): Response
    {
        try {
            $request = $this->createRequest($method, $this->makeUri($uri), $headers, $body, null, $options);
            $response = $this->send($request);

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
     * Create a new request object.
     *
     * @param string $method
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param null   $version
     * @param array  $options
     *
     * @return Request
     */
    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = null,
        array $options = []
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
            if (!isset($headers['Accept'])) {
                $headers['Accept'] = 'application/json';
            }
        }

        // Create a Guzzle request
        $request = new \GuzzleHttp\Psr7\Request($this->sanitizeMethod($method), $this->makeUri($uri), $headers,
            $body ?? null, $version ?? 1.1);

        // Upgrade the Request to our native Request object
        return Request::fromRequest($request)->setOptions($options);
    }

    /**
     * Send a Request object and get a Response.
     *
     * @param Request $request
     *
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(Request $request): Response
    {
        // Send the request and get a response object
        $response = Response::fromResponse($this->getGuzzleClient()->send($request, $request->getOptions()));
        $response->setRequest($request);

        return $response;
    }

    /**
     * Prepends the base URI to any non-absolute URI.
     *
     * @param string $uri
     *
     * @return string
     */
    public function url($uri)
    {
        $url = $uri;

        if (!preg_match('#^https?://#', $uri)) {
            $url = strpos($uri, '/') !== 0 ? "/$uri" : $uri;
            if ($this->baseUri) {
                $url = preg_replace('#/$#', '', $this->baseUri).$url;
            }
        }

        return $url;
    }

    /**
     * Returns the Logger instance.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the Logger instance which will be used to log requests and responses.
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the underlying Guzzle client used to transport the requests.
     *
     * @return GuzzleClient
     */
    public function getGuzzleClient(): GuzzleClient
    {
        return $this->guzzle;
    }

    /**
     * @param GuzzleClient $guzzle
     *
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
     *
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
     *
     * @param string $uri
     *
     * @return $this
     */
    public function setBaseUri($uri)
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * Get a header by key.
     *
     * @param string      $key
     * @param string|null $default
     *
     * @return string|null
     */
    public function getHeader($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Set/replace a header by key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = (string) $value;

        return $this;
    }

    /**
     * Replace the headers with the supplied array (key => value).
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Create a new instance of the client with additional headers set.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return (clone $this)->addHeaders($headers);
    }

    /**
     * Merge a new set of headers with any existing headers.  (Existing keys are overwritten).
     *
     * @param array $headers
     *
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers ?? [], $headers);

        return $this;
    }

    /**
     * Log a message via the logger.
     *
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
     * Merges the given headers with the global headers stored in the instance.
     *
     * @param array $headers
     *
     * @return array
     */
    protected function mergeHeaders(array $headers): array
    {
        return array_merge($this->headers, $headers);
    }

    /**
     * Sanitizes an HTTP method.
     *
     * @param $method
     *
     * @return string
     */
    protected function sanitizeMethod(string $method): string
    {
        return strtoupper(trim($method));
    }

    /**
     * @param string|UriInterface $uri
     *
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
