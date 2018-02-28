<?php


namespace Rexlabs\HyperHttp\Message;


trait ContentTypeTrait
{
    /**
     * Returns the content type of the response.
     * @return string
     */
    public function contentType()
    {
        return $this->getHeaderLine('Content-Type');
    }

    /**
     * Determines if the response is a JSON response by interrogating the headers.
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->contentType();
        if ($contentType === 'application/json') {
            return true;
        }
        if (preg_match('#^application/.+\+json$#i', $contentType)) {
            return true;
        }

        return false;
    }
}