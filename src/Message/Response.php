<?php

namespace Rexlabs\HyperHttp\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rexlabs\ArrayObject\ArrayObject;
use Namshi\Cuzzle\Formatter\CurlFormatter;

/**
 * Class Response.
 *
 * @mixin \Rexlabs\ArrayObject\ArrayObject
 */
class Response extends \GuzzleHttp\Psr7\Response
{
    use ContentTypeTrait;

    /** @var RequestInterface */
    protected $request;

    /** @var ArrayObject|null */
    protected $arrayObject;

    /**
     * Upgrades an existing Response which implements the Guzzle response interface
     * into a Hyper Response object.
     *
     * @param ResponseInterface $response
     *
     * @return static
     */
    public static function fromResponse(ResponseInterface $response)
    {
        return new static(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    /**
     * Gets the request associated with this response.
     *
     * @return null|RequestInterface|Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the request associated with this response.
     *
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get curl command output for this response's request.
     *
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
     * Converts the Response body to an array.
     * If the body cannot be converted to an array, an empty array [] will be returned.
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->arrayObject !== null) {
            return $this->arrayObject->toArray();
        }

        $arr = [];
        if ($this->isJson()) {
            $arr = \GuzzleHttp\json_decode($this->getBody(), true);
            if (!\is_array($arr)) {
                $arr = [];
            }
        }

        return $arr;
    }

    /**
     * Converts the array content of the response to an ArrayObject instance.
     * If the response body is not convertible to an array, then an empty ArrayObject is returned.
     * Note: the object is cached after the first call to this method.
     *
     * @return ArrayObject
     */
    public function toObject(): ArrayObject
    {
        if ($this->arrayObject === null) {
            $this->arrayObject = ArrayObject::fromArray($this->toArray());
        }

        return $this->arrayObject;
    }

    /**
     * Returns a Json (string) representation of the internal ArrayObject.
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\JsonEncodeException
     *
     * @return string
     */
    public function toJson(): string
    {
        return $this->toObject()->toJson();
    }

    /**
     * Delegates missing methods to the inner ArrayObject.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return \call_user_func_array([$this->toObject(), $name], $arguments);
    }

    /**
     * Delegates property getter to the underlying ArrayObject.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->toObject()->get($name);
    }

    /**
     * Delegates property setter to the underlying ArrayObject.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function __set($name, $value)
    {
        return $this->toObject()->set($name, $value);
    }

    /**
     * Delegates property existence to the underlying ArrayObject.
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->toObject()->has($name);
    }

    /**
     * Returns a string version of the response body.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getBody();
    }
}
