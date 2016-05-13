<?php

namespace Tests\IPC\CurlBundle;

use IPC\CurlBundle;

class FullTest extends \PHPUnit_Framework_TestCase
{

    public function testCurlRequest()
    {
        $curlRequest = new CurlBundle\CurlRequest('https://www.example.com/');
        $response = $curlRequest->exec();
        $this->assertRegExp('~<title>Example Domain</title>~', $response->getBody());
    }

    public function testMultiCurlService()
    {
        $handler = new CurlBundle\MultiCurlHandler();
        $curlRequest1 = new CurlBundle\CurlRequest('http://www.example.com/');
        $curlRequest2 = new CurlBundle\CurlRequest('http://www.example.com/');
        $handler->addRequest($curlRequest1);
        $handler->addRequest($curlRequest2);
        $response2 = $handler->getResponse($curlRequest2);
        $this->assertInstanceOf(CurlBundle\Response::class, $response2);
        $response1 = $handler->getResponse($curlRequest1);
        $this->assertInstanceOf(CurlBundle\Response::class, $response1);
    }
}
