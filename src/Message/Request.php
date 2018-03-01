<?php

namespace Rexlabs\HyperHttp\Message;

use Namshi\Cuzzle\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;

class Request extends \GuzzleHttp\Psr7\Request
{
    use ContentTypeTrait, FormTrait;

    /** @var array */
    protected $options = [];

    /**
     * Upgrade the given request to a native one.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface|Request|static
     */
    public static function fromRequest(RequestInterface $request)
    {
        return $request instanceof static ? $request : new static($request->getMethod(), $request->getUri(),
            $request->getHeaders(), $request->getBody(), $request->getProtocolVersion());
    }

    /**
     * Get curl command output for this request.
     *
     * @return string
     */
    public function getCurl()
    {
        return (new CurlFormatter())->format($this);
    }

    /**
     * Save the request options with the request.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Retrieve the saved request options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?? [];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getBody();
    }
}
