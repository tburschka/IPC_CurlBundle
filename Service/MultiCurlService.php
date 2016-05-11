<?php

namespace IPC\CurlBundle\Service;

use IPC\CurlBundle\RequestInterface;
use IPC\CurlBundle\Response;

class MultiCurlService
{

    /**
     * cURL multi handle
     *
     * @var resource
     */
    private $curlMultiHandle;

    /**
     * @var array
     */
    private $timers;

    /**
     * @var bool
     */
    private $running;

    /**
     * @var int
     */
    private $status;

    /**
     * @var RequestInterface[]
     */
    private $requests = [];

    /**
     * @var array
     */
    private $responses = [];


    protected $sleepIncrement = 1.1;

    public function __construct()
    {
        $this->curlMultiHandle = curl_multi_init();
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
        $curlHandle = $request->getCurlHandle();
        $key = $request->getKey();
        $this->requests[$key] = $request;

        // processing header to store headers for response in array
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
        $code = curl_multi_add_handle($this->curlMultiHandle, $request->getCurlHandle());

        // if no error returned, call multi exec for the new handle
        if (CURLM_OK === $code || CURLM_CALL_MULTI_PERFORM === $code) {
            $this->startTimer($curlHandle);
            $this->status = curl_multi_exec($this->curlMultiHandle, $this->running);
        }

        return $code;
    }

    /**
     * @param RequestInterface $request
     * @return Response|false
     */
    public function getResponse(RequestInterface $request)
    {
        $key = $request->getKey();
        if (isset($this->responses[$key]['object'])) {
            return $this->responses[$key]['object'];
        } else {

            $innerSleepInt = $outerSleepInt = 1;
            while ($this->running && ($this->status == CURLM_OK || $this->status == CURLM_CALL_MULTI_PERFORM)) {

                usleep(intval($outerSleepInt));
                $outerSleepInt = intval(max(1, ($outerSleepInt * $this->sleepIncrement)));
                $multiSelect = curl_multi_select($this->curlMultiHandle, 0);
                /* @see https://bugs.php.net/bug.php?id=63411 */
                if ($multiSelect === -1) {
                    usleep(100000);
                }

                /* @see https://curl.haxx.se/libcurl/c/libcurl-errors.html */
                if ($multiSelect >= CURLM_CALL_MULTI_PERFORM) {
                    do {
                        $this->status = curl_multi_exec($this->curlMultiHandle, $this->running);
                        usleep(intval($innerSleepInt));
                        $innerSleepInt = intval(max(1, ($innerSleepInt * $this->sleepIncrement)));
                    } while ($this->status === CURLM_CALL_MULTI_PERFORM);
                    $innerSleepInt = 1;
                }

                while ($done = curl_multi_info_read($this->curlMultiHandle)) {
                    $currentHandle = $done['handle'];
                    $currentKey    = $this->getKey($currentHandle);
                    $this->stopTimer($currentHandle);
                    $this->responses[$currentKey]['body'] = curl_multi_getcontent($currentHandle);
                    foreach ($this->getResponseProperties() as $name => $const) {
                        $this->responses[$currentKey][$name] = curl_getinfo($currentHandle, $const);
                    }
                    curl_multi_remove_handle($this->curlMultiHandle, $currentHandle);
                    curl_close($currentHandle);
                    // FIXME: $currentHandle isn't closed here
                }

                if (isset($this->responses[$key]['body'])) {
                    $this->responses[$key]['object'] = new Response($this->responses[$key]);
                    return $this->responses[$key]['object'];
                }
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
    protected function getKey($curlHandle)
    {
        return (string)$curlHandle;
    }

    /**
     * Start the timer
     *
     * @param resource $curlHandle
     */
    protected function startTimer($curlHandle)
    {
        $this->responses[$this->getKey($curlHandle)]['start'] = microtime(true);
    }

    /**
     * @param resource $curlHandle
     */
    protected function stopTimer($curlHandle)
    {
        $key = $this->getKey($curlHandle);
        $this->responses[$key]['end']  = microtime(true);
        $this->responses[$key]['url']  = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        $this->responses[$key]['time'] = curl_getinfo($curlHandle, CURLINFO_TOTAL_TIME);
        $this->responses[$key]['code'] = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
    }

    /**
     * @param resource $curlHandle
     * @param string   $header
     * @return int
     */
    private function headerCallback($curlHandle, $header)
    {
        $trimmedHeader = trim($header);
        $colonPosition = strpos($trimmedHeader, ':');
        if ($colonPosition > 0) {
            $key = substr($trimmedHeader, 0, $colonPosition);
            $val = preg_replace('/^\W+/', '', substr($trimmedHeader, $colonPosition));
            $this->responses[$this->getKey($curlHandle)]['headers'][$key] = $val;
        }
        return strlen($header);
    }

    /**
     * @return array
     */
    protected function getResponseProperties()
    {
        return [
            'url'   => CURLINFO_EFFECTIVE_URL,
            'time'  => CURLINFO_TOTAL_TIME,
            'code'  => CURLINFO_HTTP_CODE,
            'length'=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,
            'type'  => CURLINFO_CONTENT_TYPE,
        ];
    }
}
