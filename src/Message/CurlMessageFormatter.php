<?php

namespace Rexlabs\HyperHttp\Message;

use GuzzleHttp\MessageFormatter;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CurlMessageFormatter.
 */
class CurlMessageFormatter extends MessageFormatter
{
    /**
     * Returns a formatted message string.
     *
     * @param RequestInterface  $request  Request that was sent
     * @param ResponseInterface $response Response that was received
     * @param \Exception        $error    Exception that was received
     *
     * @return string
     */
    public function format(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $error = null
    ) {
        return $response === null
            ? (new CurlFormatter())->format($request)
            : parent::format($request, $response, $error);
    }
}
