<?php

declare(strict_types=1);

namespace Riesenia\DPDShipment;

/**
 * Class Api.
 */
class Api
{
    /** @var string */
    protected $clientKey;

    /** @var string */
    protected $email;

    /** @var string */
    protected $endpoint = 'https://api.dpdportal.sk/shipment/json';

    /** @var string */
    protected $password;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $errors = [];

    /**
     * Api constructor.
     *
     * @param string $clientKey
     * @param string $email
     * @param string $password
     * @param array  $options
     */
    public function __construct(string $clientKey, string $email, string $password, array $options = [])
    {
        $this->clientKey = $clientKey;
        $this->email = $email;
        $this->password = $password;
        $this->options = $options + ['testMode' => false, 'timeout' => 10];

        if ($this->options['testMode']) {
            $this->endpoint = 'https://capi.dpdportal.sk/apix/shipment/json';
        }
    }

    /**
     * Create shipment and return label url on success.
     *
     * @param array $shipment
     *
     * @return string
     */
    public function send(array $shipment): string
    {
        $response = $this->sendRequest('create', [
            'shipment' => $shipment
        ]);

        return (string) $response['result']['result']['label'];
    }

    /**
     * Send cURL request.
     *
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function sendRequest(string $method, array $data): array
    {
        $data['DPDSecurity'] = [
            'SecurityToken' => [
                'ClientKey' => $this->clientKey,
                'Email' => $this->email,
                'Password' => $this->password
            ]
        ];

        $postData = [
            'id' => 1,
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $data
        ];

        $ch = \curl_init($this->endpoint);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($postData));
        \curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);

        $response = \json_decode(\curl_exec($ch), true);

        if (\curl_errno($ch)) {
            throw new ShipmentApiException('Request failed: ' . \curl_error($ch));
        }

        \curl_close($ch);

        if (isset($response['result']['result'][0]) && !(bool) $response['result']['result'][0]['success']) {
            throw new ShipmentApiException(\implode(' ', \array_column($response['result']['result'][0]['messages'], 'value')));
        }

        return $response;
    }
}
