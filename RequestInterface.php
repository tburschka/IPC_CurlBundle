<?php

namespace IPC\CurlBundle;

interface RequestInterface
{

    /**
     * Get the cURL handle
     *
     * @return resource
     */
    public function getCurlHandle();

    /**
     * Set multiple cURL options
     *
     * @param array $options
     *
     * @return bool
     */
    public function setOptions(array $options);

    /**
     * Set single cURL option
     *
     * @param string|int $option
     * @param mixed      $value
     *
     * @return bool
     */
    public function setOption($option, $value);

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders();

    /**
     * Execute cURL request and get a response
     *
     * @return Response
     */
    public function exec();
}
