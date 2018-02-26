<?php
namespace Rexlabs\HyperHttp\Message;

use Namshi\Cuzzle\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;

class Request extends \GuzzleHttp\Psr7\Request
{
    public static function fromRequest(RequestInterface $request)
    {
        return new static(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        );
    }

    public function getCurl()
    {
        return (new CurlFormatter())->format($this);
    }
}