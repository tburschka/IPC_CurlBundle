<?php

namespace IPC\CurlBundle;

interface RequestInterface
{

    /**
     * Returns the cURL handle
     *
     * @return resource
     */
    public function getCurlHandle();

    /**
     * Set cURL options
     *
     * @param array $options
     *
     * @return mixed
     */
    public function setOptions(array $options);

    /**
     * Get cURL transfer info
     *
     * @param int $opt
     * @return mixed
     */
    public function getInfo($opt = null);

    /**
     * @return
     */
    public function exec();

    /**
     * @return string
     */
    public function getKey();
}
