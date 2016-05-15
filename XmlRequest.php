<?php

namespace IPC\CurlBundle;

class XmlRequest extends CurlRequest
{

    /**
     * XmlRequest constructor
     *
     * @param string $url
     * @param null   $curlHandle
     *
     * @throws RequestException
     */
    public function __construct($url, $curlHandle = null)
    {
        parent::__construct($url, $curlHandle);
        $this->setOption(CURLOPT_HTTPHEADER, ['Content-type : application/xml', 'Accept : application/xml']);
    }

    /**
     * Set cURL body
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function setBody($data)
    {
        return $this->setOption(CURLOPT_POSTFIELDS, $data);
    }
}
