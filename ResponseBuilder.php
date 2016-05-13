<?php

namespace IPC\CurlBundle;

class ResponseBuilder
{

    /**
     * @param resource $curlHandle
     * @param array    $headers
     * @param string   $body
     * @param float    $start
     * @param float    $end
     *
     * @return Response
     */
    public function buildResponse($curlHandle, $headers, $body, $start, $end)
    {
        $data = [
            'headers' => $headers,
            'body'    => $body,
            'start'   => $start,
            'end'     => $end,
        ];

        $properties = [
            'url'   => CURLINFO_EFFECTIVE_URL,
            'time'  => CURLINFO_TOTAL_TIME,
            'code'  => CURLINFO_HTTP_CODE,
            'length'=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,
            'type'  => CURLINFO_CONTENT_TYPE,
        ];

        foreach ($properties as $name => $const) {
            $data[$name] = curl_getinfo($curlHandle, $const);
        }

        // fix data type
        $data['length'] = (int) $data['length'];

        return new Response($data);
    }
}
