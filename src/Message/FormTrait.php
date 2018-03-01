<?php

namespace Rexlabs\HyperHttp\Message;

trait FormTrait
{
    /**
     * Get the form data for this form.
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getFormData()
    {
        if ($this->isUrlEncodedForm()) {
            return $this->getOptions()['form_params'] ?? [];
        }

        if ($this->isMultipartForm()) {
            return $this->getOptions()['multipart'] ?? [];
        }

        throw new \RuntimeException('Cannot get form data from '.$this->contentType());
    }

    /**
     * Determines if this request is a form based on the request headers.
     *
     * @return bool
     */
    public function isForm()
    {
        if ($this->isUrlEncodedForm()) {
            return true;
        }
        if ($this->isMultipartForm()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this request is a urlencoded form.
     *
     * @return bool
     */
    public function isUrlEncodedForm()
    {
        return $this->getMethod() === 'POST' && $this->contentType() === 'application/x-www-form-urlencoded';
    }

    /**
     * Determine if this request is a multipart form.
     *
     * @return bool
     */
    public function isMultipartForm()
    {
        return $this->getMethod() === 'POST' && $this->contentType() === 'multipart/form-data';
    }
}
