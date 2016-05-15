<?php

namespace IPC\CurlBundle;

class JsonRequest extends CurlRequest
{

    /**
     * JsonRequest constructor
     * 
     * @param string $url
     * @param null   $curlHandle
     * 
     * @throws RequestException
     */
    public function __construct($url, $curlHandle = null)
    {
        parent::__construct($url, $curlHandle);
        $this->setOption(CURLOPT_HTTPHEADER, ['Content-type : application/json', 'Accept : application/json']);
    }

    /**
     * Set cURL body
     *
     * @param mixed $data
     * @param bool  $encode
     *
     * @return bool
     */
    public function setBody($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        return $this->setOption(CURLOPT_POSTFIELDS, $data);
    }
}
