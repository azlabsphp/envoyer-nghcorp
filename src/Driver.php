<?php

namespace Drewlabs\Envoyer\Drivers\NGHCorp;

use Drewlabs\Envoyer\Contracts\ClientInterface;
use Drewlabs\Envoyer\Contracts\NotificationInterface;
use Drewlabs\Envoyer\Contracts\NotificationResult;
use Drewlabs\Curl\Client as Curl;
use Drewlabs\Envoyer\Drivers\NGHCorp\Exceptions\InvalidCredentialsException;
use Drewlabs\Envoyer\Drivers\NGHCorp\Exceptions\RequestException;

class Driver implements ClientInterface
{

    use SendsHTTPRequest;

    /** @var string */
    private  $endpoint;

    /** @var string */
    private  $apiKey;

    /** @var string */
    private  $apiSecret;

    /** @var Curl */
    private $curl;

    /**
     * Creates new NGHCorp envoyer driver instance
     * 
     * @param string $endpoint 
     * @param string|null $apiKey 
     * @param string|null $apiSecret 
     */
    public function __construct(string $endpoint, string $apiKey = null, string $apiSecret = null)
    {
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->curl = new Curl(rtrim($endpoint, '/'));
    }


    /**
     * Creates new `NGHCorp` envoyer driver instance
     * 
     * @param string $endpoint 
     * @param string|null $apiKey 
     * @param string|null $apiSecret 
     * @return static 
     */
    public static function new(string $endpoint, string $apiKey = null, string $apiSecret = null)
    {
        return new static($endpoint, $apiKey, $apiSecret);
    }


    /**
     * Override instance api key and secret properties
     * 
     * **Note** It creates a copy of the instance using
     *          PHP `clone` function instead of modifying existing instance
     * 
     * @param string $apiKey 
     * @param string $apiSecret
     * @return static 
     */
    public function withCredentials(string $apiKey, string $apiSecret)
    {
        $self = clone $this;
        $self->apiKey = $apiKey;
        $self->apiSecret = $apiSecret;

        return $self;
    }

    public function sendRequest(NotificationInterface $instance): NotificationResult
    {
        if (is_null($this->apiKey) || is_null($this->apiSecret)) {
            throw new InvalidCredentialsException("Authorization credentials was not provided. Please call the withCredentials() to pass in the api key and secret variables.");
        }

        $response = $this->sendHTTPRequest($this->curl, '/api/send-sms', 'POST', [
            "from" => $instance->getSender()->__toString(),
            "to" => $instance->getReceiver()->__toString(),
            "text" => strval($instance->getContent()),
            "reference" => $instance->id() ?? uniqid(time()),
            "api_key" => $this->apiKey,
            "api_secret" => $this->apiSecret
        ], [
            'Content-Type' => 'application/json'
        ]);
        if (($statusCode  = $response->getStatusCode()) && (200 > $statusCode || 204 < $statusCode)) {
            throw new RequestException(sprintf("/POST /api/send-sms fails with status %d -  %s", $statusCode, $response->getBody()));
        }

        // we query for the decoded json payload from the response
        $decoded = $response->json()->getBody();


        // case the decoded response has a status field and the status is not equals to 200
        // base on the API specification, an error has occured while sending message to the recipient.
        // In such case, we throw a request exception object containing the error message and error code
        if (isset($decoded['status']) && ($status = intval($decoded['status'])) !== 200) {
            throw new RequestException(ErrorCodes::message($status), $status);
        }

        return Result::fromJson($decoded);
    }
}
