<?php

namespace IPC\CurlBundle;

class CurlRequest implements RequestInterface
{

    /**
     * The cURL handle
     *
     * @var resource
     */
    protected $curlHandle;

    /**
     * Key for the cURL handle
     * 
     * @var string
     */
    protected $key;

    /**
     * CurlRequest constructor.
     *
     * @param string        $url
     * @param null|resource $curlHandle
     *
     * @throws RequestException
     */
    public function __construct($url, $curlHandle = null)
    {
        if (!is_string($url)) {
            throw new RequestException('url has to be a string');
        }
        if (is_null($curlHandle)) {
            $this->curlHandle = curl_init($url);
            curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, 1);
        } elseif ($this->isCurlHandle($curlHandle)) {
            $this->curlHandle = $curlHandle;
            curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        } else {
            throw new RequestException('curlHandle has to be a valid curl handle');
        }
        $this->key = (string)$this->curlHandle;
    }

    /**
     * Return the key for the cURL handle
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the cURL handle
     *
     * @return resource
     */
    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * Set cURL options
     *
     * @param array $options
     *
     * @return mixed
     */
    public function setOptions(array $options)
    {
        return curl_setopt_array($this->curlHandle, $options);
    }

    /**
     * Execute a single cURL request
     *
     * @return mixed
     * @throws RequestException
     */
    public function exec()
    {
        if (!$this->isCurlHandle($this->curlHandle)) {
            throw new RequestException('curlHandle is already closed');
        }
        return curl_exec($this->curlHandle);
    }

    /**
     * Check if the given handle is a cURL handle
     *
     * @param $curlHandle
     * @return bool
     */
    protected function isCurlHandle($curlHandle)
    {
        return is_resource($curlHandle);
    }

    /**
     * Get cURL transfer info
     *
     * @param int $opt
     * @return mixed
     */
    public function getInfo($opt = null)
    {
        return curl_getinfo($this->curlHandle, $opt);
    }
}
