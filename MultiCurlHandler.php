<?php

namespace IPC\CurlBundle;

class MultiCurlHandler
{

    /**
     * cURL multi handle
     *
     * @var resource
     */
    private $curlMultiHandle;

    /**
     * Is cURL multi running
     *
     * @var bool
     */
    private $running;

    /**
     * Status of cURL multi exec
     *
     * @var int
     */
    private $status;

    /**
     * Storage of the added requests
     *
     * @var RequestInterface[]
     */
    private $requests = [];

    /**
     * Storage of responses/response data
     *
     * @var array|Response[]
     */
    private $responses = [];

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * MultiCurlService constructor
     */
    public function __construct()
    {
        $this->curlMultiHandle = curl_multi_init();
        $this->responseBuilder = new ResponseBuilder();
    }

    /**
     * Add a normal cURL handle to the cURL multi handle
     *
     * @param RequestInterface $request
     *
     * @return int 0 on success, or one of the CURLM_XXX errors
     */
    public function addRequest(RequestInterface $request)
    {
        $curlHandle           = $request->getCurlHandle();
        $key                  = $this->getKey($curlHandle);
        $this->requests[$key] = $request;

        // add callback for processing and storing response headers
        curl_setopt($curlHandle, CURLOPT_HEADERFUNCTION, function ($curlHandle, $header) {
            $trimmedHeader = trim($header);
            $colonPosition = strpos($trimmedHeader, ':');
            if ($colonPosition > 0) {
                $key = substr($trimmedHeader, 0, $colonPosition);
                $val = preg_replace('/^\W+/', '', substr($trimmedHeader, $colonPosition));
                $this->responses[$this->getKey($curlHandle)]['headers'][$key] = $val;
            }
            return strlen($header);
        });

        $code = curl_multi_add_handle($this->curlMultiHandle, $curlHandle);

        // if no error returned, call multi exec for the new handle
        if (CURLM_OK === $code || CURLM_CALL_MULTI_PERFORM === $code) {
            $this->responses[$key]['start'] = microtime(true);
            $this->status = curl_multi_exec($this->curlMultiHandle, $this->running);
        }

        return $code;
    }

    /**
     * Get added requests
     *
     * @return RequestInterface[]
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * Get a response based on the request
     *
     * @param RequestInterface $request
     *
     * @return Response|false
     */
    public function getResponse(RequestInterface $request)
    {
        $sleepIncrement = 1.1;
        $key            = $this->getKey($request->getCurlHandle());

        // check for existing response/response data
        if (array_key_exists($key, $this->responses)) {
            if (array_key_exists('body', $this->responses[$key])) {
                $this->transformResponse($request);
            }
            if ($this->responses[$key] instanceof Response) {
                return $this->responses[$key];
            }
        }

        $innerSleep = $outerSleep = 1;
        while ($this->running && ($this->status === CURLM_OK || $this->status === CURLM_CALL_MULTI_PERFORM)) {
            usleep((int)$outerSleep);
            $outerSleep = (int) max(1, $outerSleep * $sleepIncrement);

            // blocks until activity on sockets
            curl_multi_select($this->curlMultiHandle, 0);

            // wait for response
            do {
                $this->status = curl_multi_exec($this->curlMultiHandle, $this->running);
                usleep((int) $innerSleep);
                $innerSleep = (int) max(1, $innerSleep * $sleepIncrement);
            } while ($this->status === CURLM_CALL_MULTI_PERFORM);
            $innerSleep = 1;

            $this->saveCompletedResponseData();

            if (isset($this->responses[$key]['body'])) {
                $this->transformResponse($request);
                return $this->responses[$key];
            }
        }

        return false;
    }

    /**
     * Set an option for the cURL multi handle
     *
     * @param int $option One of the CURLMOPT_* constants
     * @param int $value  The value to be set on option
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function setOption($option, $value)
    {
        return curl_multi_setopt($this->curlMultiHandle, $option, $value);
    }

    /**
     * Return the key for a cURL handle
     *
     * @param resource $curlHandle
     *
     * @return string
     */
    private function getKey($curlHandle)
    {
        return (string) $curlHandle;
    }

    /**
     * Save body and stop-time for all completed responses
     */
    private function saveCompletedResponseData()
    {
        while ($done = curl_multi_info_read($this->curlMultiHandle)) {
            $curlHandle                    = $done['handle'];
            $key                           = $this->getKey($curlHandle);
            $this->responses[$key]['end']  = microtime(true);
            $this->responses[$key]['body'] = curl_multi_getcontent($curlHandle);
        }
    }

    /**
     * Transform the response data to response and closes the cURL connection
     *
     * @param RequestInterface $request
     */
    private function transformResponse(RequestInterface $request)
    {
        $curlHandle = $request->getCurlHandle();
        $key        = $this->getKey($curlHandle);
        $this->responses[$key] = $this->responseBuilder->buildResponse(
            $curlHandle,
            $request->getResponseHeaders(),
            $this->responses[$key]['body'],
            $this->responses[$key]['start'],
            $this->responses[$key]['end']
        );
        curl_multi_remove_handle($this->curlMultiHandle, $curlHandle);
        curl_close($curlHandle);
    }
}
