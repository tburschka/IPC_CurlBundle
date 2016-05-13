<?php

namespace IPC\CurlBundle;

class Response
{

    /**
     * Response headers
     * 
     * @var array
     */
    protected $headers;

    /**
     * Response body
     * 
     * @var string
     */
    protected $body;

    /**
     * Effective url
     * 
     * @var string
     */
    protected $url;

    /**
     * Content type
     *
     * @var string
     */
    protected $type;

    /**
     * Status code
     *
     * @var int
     */
    protected $code;

    /**
     * Content length
     *
     * @var int
     */
    protected $length;

    /**
     * Measured start time
     *
     * @var float
     */
    protected $start;

    /**
     * Measured end time
     *
     * @var float
     */
    protected $end;

    /**
     * The cURL execution time
     *
     * @var float
     */
    protected $time;

    /**
     * Response constructor
     *
     * @param array $responseData
     */
    public function __construct($responseData)
    {
        foreach ($responseData as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Get response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get effective url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get status code
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get content length
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get measured start time
     *
     * @return float
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Get measured end time
     *
     * @return float
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get cURL execution time
     *
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }
}
