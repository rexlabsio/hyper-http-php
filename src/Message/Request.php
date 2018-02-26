<?php
namespace Rexlabs\HyperHttp\Message;

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
}