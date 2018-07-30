<?php

namespace Rexlabs\HyperHttp\Message;

trait ContentTypeTrait
{
    /**
     * Returns the content type of the response.
     *
     * @return string
     */
    public function contentType()
    {
        return preg_split('/\s*;\s*/', $this->getHeaderLine('Content-Type'), 2)[0];
    }

    /**
     * Determines if the response is a JSON response by interrogating the headers.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->contentType();
        if (preg_match('#^application/(.+\+)?json$#i', $contentType)) {
            return true;
        }
        return false;
    }
}
