<?php
namespace Rexlabs\HyperHttp\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rexlabs\ArrayObject\ArrayObject;

/**
 * Class Response
 * @mixin \Rexlabs\ArrayObject\ArrayObject
 * @package Rexlabs\HyperHttp\Message
 */
class Response extends \GuzzleHttp\Psr7\Response
{
    /** @var RequestInterface */
    protected $request;

    /** @var ArrayObject|null */
    protected $data;

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
     * @return null|RequestInterface|Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RequestInterface $request
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    public function isJson(): bool
    {
        $contentType = $this->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }
        if (preg_match('/json$/i', $contentType)) {
            return true;
        }

        return false;
    }

    public function toArray(): array
    {
        $arr = [];
        if ($this->isJson()) {
            $arr = \GuzzleHttp\json_decode($this->getBody(), true);
        }

        return \is_array($arr) ? $arr : [];
    }

    public function toObject(): ArrayObject
    {
        if ($this->data === null) {
            $this->data = ArrayObject::fromArray($this->toArray());
        }

        return $this->data;
    }

    public function __call($name, $arguments)
    {
        return \call_user_func_array([$this->toObject(), $name], $arguments);
    }

    public function __get($name)
    {
        return $this->toObject()->get($name);
    }

    public function __set($name, $value)
    {
        return $this->toObject()->set($name, $value);
    }

    public function __isset($name)
    {
        return $this->toObject()->has($name);
    }

    public function __toString()
    {
        return (string)$this->getBody();
    }
}