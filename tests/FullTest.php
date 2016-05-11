<?php

namespace Tests\IPC\CurlBundle;

use IPC\CurlBundle;

class FullTest extends \PHPUnit_Framework_TestCase
{

    public function testCurlRequest()
    {
        $curlRequest = new CurlBundle\CurlRequest('https://www.example.com/');
        $response = $curlRequest->exec();
        $this->assertRegExp('~<title>Example Domain</title>~', $response);
    }

    public function testMultiCurlService()
    {
        $service = new CurlBundle\Service\MultiCurlService();
        $curlRequest1 = new CurlBundle\CurlRequest('http://www.example.com/');
        $curlRequest2 = new CurlBundle\CurlRequest('http://www.example.com/');
        $service->addRequest($curlRequest1);
        usleep(1000);
        $service->addRequest($curlRequest2);
        $response2 = $service->getResponse($curlRequest2);
        var_dump($response2);
        $response1 = $service->getResponse($curlRequest1);
        var_dump(is_array($response1));
        $response2 = $service->getResponse($curlRequest2);
        var_dump((string) $curlRequest2->getCurlHandle());
        var_dump(is_array($response2), curl_getinfo($curlRequest2->getCurlHandle(), CURLINFO_EFFECTIVE_URL));
    }
}
