<?php

namespace Rexlabs\HyperHttp\Message;

use GuzzleHttp\MessageFormatter;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class CurlMessageFormatter.
 */
class CurlMessageFormatter extends MessageFormatter
{
    /**
     * Returns a formatted message string.
     *
     * @param RequestInterface $request Request that was sent
     * @param ResponseInterface|null $response Response that was received
     * @param Throwable|null $error Exception that was received
     *
     * @return string
     */
    public function format(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $error = null
    ): string {
        return $response === null
            ? (new CurlFormatter())->format($request)
            : parent::format($request, $response, $error);
    }
}
