<?php

declare(strict_types=1);

namespace Drewlabs\Envoyer\Drivers\NGHCorp;

use Drewlabs\Curl\Client as Curl;
use Drewlabs\Curl\Utils\Response;
use Drewlabs\Envoyer\Drivers\NGHCorp\Exceptions\RequestException;

trait SendsHTTPRequest
{

    /**
     * Send request to the backend server and return the result to the caller
     * 
     * @param Curl $curl
     * @param string $endpoint 
     * @param string $method 
     * @param array $body 
     * @param array $headers 
     * @return Response 
     * @throws RuntimeException 
     * @throws RequestException 
     */
    private function sendHTTPRequest(Curl $curl, string $endpoint, string $method = 'GET', array $body = [], array $headers = []): Response
    {
        // Reset the current curl instance before sending any new HTTP request
        $this->resetCurl($curl);

        // Sends the request to the coris webservice host
        $curl->send([
            'method' => $method,
            'url' => $endpoint,
            'headers' => array_merge([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $headers ?? []),
            'body' => $body
        ]);

        if (200 !== ($statusCode = $curl->getStatusCode())) {
            throw new RequestException("/GET $endpoint : Unknown Request error", $statusCode);
        }

        return new Response($curl->getResponse() ?? '', intval($statusCode), $this->parseHeaders($curl->getResponseHeaders() ?? ''));
    }

    /**
     * Reset the curl client to it default options.
     *
     * @param Curl $curl
     * 
     * @return void
     */
    private function resetCurl(Curl $curl)
    {
        // First we release the current client resources
        $curl->release();
        $curl->init();
        // Disable ssl verification to avoid any SSL error
        $curl->disableSSLVerification();
        $curl->setOption(\CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Parse request string headers.
     *
     * @param ?string $headers
     *
     * @return array
     */
    private function parseHeaders(string $headers)
    {
        $headers = preg_split('/\r\n/', (string) ($headers ?? ''), -1, \PREG_SPLIT_NO_EMPTY);
        $httpHeaders = [];
        $httpHeaders['Request-Line'] = reset($headers) ?? '';
        for ($i = 1; $i < \count($headers); ++$i) {
            if (str_contains($headers[$i], ':')) {
                [$key, $value] = array_map(static function ($item) {
                    return $item ? trim($item) : null;
                }, explode(':', $headers[$i], 2));
                $httpHeaders[$key] = $value;
            }
        }

        return $httpHeaders;
    }
}
