<?php

namespace Rexlabs\HyperHttp\Exceptions;

use Namshi\Cuzzle\Formatter\CurlFormatter;
use Throwable;

class ResponseException extends ApiException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
}
