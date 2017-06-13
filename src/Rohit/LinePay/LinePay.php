<?php

namespace Rohit\LinePay;

use GuzzleHttp\Client;
use Illuminate\Http\Response;

class LinePay
{
    protected $client;

    /**
     * Creates New instances of app and request
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Process Payment
     *
     * @param array $params
     *
     * @return array
     */
    public function processPayment(array $params) : array
    {
        $request  = json_encode($params);
        $response = $this->client->post(config('line-pay.reservation-url'), [
            'headers' => [
                'X-LINE-ChannelId' => config('line-pay.channel-id'),
                'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8'
            ],
            'body' => $request,
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response  = $response->getBody();
            $content  = json_decode($response);
        }

        return [
            'status' => ($code === Response::HTTP_OK) ? 'success' : 'failed',
            'data' => [
                'request-payload' => $request,
                'response-payload' => $response ?? null,
                'transaction-id' => $content->info->transactionId ?? null,
                'url' => $content->info->paymentUrl->web ?? null,
            ],
        ];
    }

    /**
     * Verify Payment
     *
     * @param int $transactionId
     * @param array $params
     *
     * @return array
     */
    public function verifyPayment(string $transactionId, array $params) : array
    {
        $request  = json_encode($params);
        $response = $this->client->post(config('line-pay.detail-url') . '/' . $transactionId . '/confirm', [
            'headers' => [
                'X-LINE-ChannelId' => config('linepay.channel-id'),
                'X-LINE-ChannelSecret' => config('linepay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'body' => $request,
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response  = $response->getBody();
            $content  = json_decode($response);
        }

        return [
            'status' =>  (($content->returnCode ?? null) == '0000') ? 'success' : 'failed',
            'data' => [
                'request-payload' => $request,
                'response-payload' => $response ?? null,
                'transaction-id' => $transactionId,
            ],
        ];
    }
}
