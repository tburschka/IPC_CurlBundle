<?php

namespace IPC\CurlBundle;

class Response
{
    protected $headers;

    protected $body;

    protected $url;

    protected $type;

    protected $code;

    protected $length;

    protected $start;

    protected $end;

    protected $time;

    public function __construct($responseData)
    {
        foreach ($responseData as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getUrl()
    {
        return $this->url;
    }
    public function getType()
    {
        return $this->type;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getTime()
    {
        return $this->time;
    }
}
