<?php

namespace Rohit\LinePay;

use Illuminate\Validation\Factory as Validator;
use GuzzleHttp\Client;
use Illuminate\Http\Response;

class LinePay
{
    protected $client;
    protected $validator;

    /**
     * Creates New instances of app and request
     */
    public function __construct(Client $client, Validator $validator)
    {
        $this->client    = $client;
        $this->validator = $validator;
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
        $validator = $this->validator->make($params, [
            'productName' => 'required',
            'amount' => 'required|numeric',
            'currency' => 'required',
            'orderId' => 'required',
            'confirmUrl' => 'required|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            'cancelUrl' => 'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            'productImageUrl' => 'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            'checkConfirmUrlBrowser' => 'boolean',
            'capture' => 'boolean',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failed',
                'data' => [],
                'msg' => $validator->messages()->getMessages(),
            ];
        }

        $response = $this->client->post(config('line-pay.reservation-url'), [
            'headers' => [
                'X-LINE-ChannelId' => config('line-pay.channel-id'),
                'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8'
            ],
            'body' => json_encode($params),
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response = $response->getBody();
            $content  = json_decode($response->getContents(), true);
        }

        return [
            'status' => (($content['returnCode'] ?? null) == '0000') ? 'success' : 'failed',
            'data' => [
                'request' => $params,
                'response' => $content ?? [],
            ],
            'msg' => '',
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
        $validator = $this->validator->make($params, [
            'amount' => 'required|numeric',
            'currency' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failed',
                'data' => [],
                'msg' => $validator->messages()->getMessages(),
            ];
        }

        $response = $this->client->post(config('line-pay.detail-url') . '/' . $transactionId . '/confirm', [
            'headers' => [
                'X-LINE-ChannelId' => config('linepay.channel-id'),
                'X-LINE-ChannelSecret' => config('linepay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'body' => json_encode($params),
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response = $response->getBody();
            $content  = json_decode($response->getContents(), true);
        }

        return [
            'status' =>  (($content['returnCode'] ?? null) == '0000') ? 'success' : 'failed',
            'data' => [
                'request' => $params,
                'response' => $content ?? [],
            ],
            'msg' => ''
        ];
    }

    /**
     * Capture Payment
     *
     * @param string $transactionId
     * @param array $params
     *
     * @return array
     */
    public function capturePayment(string $transactionId, array $params) : array
    {
        $validator = $this->validator->make($params, [
            'amount' => 'required|numeric',
            'currency' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failed',
                'data' => [],
                'msg' => $validator->messages()->getMessages(),
            ];
        }

        $response = $this->client->post(config('line-pay.capture-url') . '/' . $transactionId . '/capture', [
            'headers' => [
                'X-LINE-ChannelId' => config('linepay.channel-id'),
                'X-LINE-ChannelSecret' => config('linepay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'body' => json_encode($params),
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response = $response->getBody();
            $content  = json_decode($response->getContents(), true);
        }

        return [
            'status' =>  (($content['returnCode'] ?? null) == '0000') ? 'success' : 'failed',
            'data' => [
                'request' => $params,
                'response' => $content ?? [],
            ],
            'msg' => ''
        ];
    }

    /**
     * Void the autorized payment
     *
     * @param string $transactionId
     *
     * @return array
     */
    public function voidPayment(string $transactionId) : array
    {
        $response = $this->client->post(config('line-pay.capture-url') . '/' . $transactionId . '/void', [
            'headers' => [
                'X-LINE-ChannelId' => config('linepay.channel-id'),
                'X-LINE-ChannelSecret' => config('linepay.channel-secret'),
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'exceptions' => false,
        ]);

        $code = $response->getStatusCode() ?? null;

        if ($code === Response::HTTP_OK) {
            $response = $response->getBody();
            $content  = json_decode($response->getContents(), true);
        }

        return [
            'status' =>  (($content['returnCode'] ?? null) == '0000') ? 'success' : 'failed',
            'data' => [
                'request' => [],
                'response' => $content ?? [],
            ],
            'msg' => ''
        ];
    }
}
