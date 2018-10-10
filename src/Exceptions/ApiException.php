<?php

namespace Rexlabs\HyperHttp\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rexlabs\HyperHttp\Message\Request;
use Rexlabs\HyperHttp\Message\Response;
use Namshi\Cuzzle\Formatter\CurlFormatter;

class ApiException extends \RuntimeException
{
    /**
     * @var null|RequestInterface
     */
    protected $request;

    /**
     * @var null|ResponseInterface
     */
    protected $response;

    /**
     * RequestException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if ($previous instanceof RequestException) {
            $this->setRequest($previous->getRequest());

            if (($response = $previous->getResponse()) !== null) {
                $this->setResponse($response);
            }
        }
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = Request::fromRequest($request);

        return $this;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = Response::fromResponse($response);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCurlRequest()
    {
        if ($this->request === null) {
            return null;
        }

        return (new CurlFormatter())->format($this->request);
    }

    /**
     * @return array
     */
    public function getResponseArray()
    {
        if ($this->response === null) {
            return [];
        }

        if ($this->response instanceof Response) {
            return $this->response->toArray();
        }

        return \GuzzleHttp\json_decode($this->response->getBody(), true);
    }
}
