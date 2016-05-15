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
     * Response headers
     *
     * @var array
     */
    protected $responseHeaders = [];

    /**
     * CurlRequest constructor
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
        if (null === $curlHandle) {
            $this->curlHandle = curl_init($url);
        } elseif ($this->isCurlHandle($curlHandle)) {
            $this->curlHandle = $curlHandle;
            $this->setOption(CURLOPT_URL, $url);
        } else {
            throw new RequestException('curlHandle has to be a valid cURL handle');
        }
        $this->setOption(CURLOPT_RETURNTRANSFER, true);

        // add callback for processing and storing response headers
        $this->setOption(CURLOPT_HEADERFUNCTION, function ($curlHandle, $header) {
            $trimmedHeader = trim($header);
            $colonPosition = strpos($trimmedHeader, ':');
            if ($colonPosition > 0) {
                $key   = substr($trimmedHeader, 0, $colonPosition);
                $value = preg_replace('/^\W+/', '', substr($trimmedHeader, $colonPosition));
                $this->responseHeaders[$key] = $value;
            }
            return strlen($header);
        });
    }

    /**
     * Get the cURL handle
     *
     * @return resource
     */
    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * Set multiple cURL options
     *
     * @param array $options
     *
     * @return bool
     */
    public function setOptions(array $options)
    {
        $result = true;
        foreach ($options as $option => $value) {
            $result &= $this->setOption($option, $value);
        }
        return $result;
    }

    /**
     * Set single cURL option
     *
     * @param string|int $option
     * @param mixed      $value
     *
     * @return bool
     */
    public function setOption($option, $value)
    {
        if (is_string($option)) {
            $option = constant($option);
        }
        return curl_setopt($this->curlHandle, $option, $value);
    }

    /**
     * Execute a single cURL request
     *
     * @return Response
     *
     * @throws RequestException
     */
    public function exec()
    {
        if (!$this->isCurlHandle($this->curlHandle)) {
            throw new RequestException('curlHandle is already closed');
        }

        $start = microtime(true);
        $body  = curl_exec($this->curlHandle);
        $end   = microtime(true);

        $builder  = new ResponseBuilder();
        $response = $builder->buildResponse($this->curlHandle, $this->getResponseHeaders(), $body, $start, $end);
        curl_close($this->curlHandle);

        return $response;
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
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }
}
