<?php
namespace Rohit\Tests;

use Illuminate\Contracts\Validation\Factory as Validator;
use Orchestra\Testbench\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Rohit\LinePay\LinePay;
use Mockery as m;

/**
 * @coversDefaultClass Rohit\LinePay\LinePay
 */
class LinePayTest extends TestCase
{
    protected $linePay;
    protected $guzzleClient;
    protected $responseMock;
    protected $streamMock;
    protected $transactionId;
    protected $validator;
    protected $returnCode;

    public function setUp()
    {
        parent::setUp();

        $this->guzzleClient = m::mock(Client::class);
        $this->validator    = app(Validator::class);
        $this->linePay      = m::mock(LinePay::class, [$this->guzzleClient, $this->validator])->makePartial();
        $this->responseMock = m::mock(Response::class);
        $this->streamMock   = m::mock(Stream::class);

        $this->responseMock->shouldReceive('getStatusCode')
            ->andReturn(200);

        $this->responseMock->shouldReceive('getBody')
            ->andReturn($this->streamMock);

        $this->transactionId = '12345';
        $this->returnCode    = '0000';
    }

    /**
     * @covers ::processPayment
     */
    public function testProcessPayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'returnCode' => $this->returnCode,
                'info' => [
                    'transactionId' => $this->transactionId,
                    'paymentUrl' => [
                        'web' => 'paymentUrl',
                    ],
                ],
            ])
        );

        $params = [
            'productName' => 'Name of Product you want user to pay for',
            'amount' => '1000',
            'currency' => 'USD',
            'orderId' => 'ORD-12345',
            'confirmUrl' => 'https://domain.com/confirm-url',
            'cancelUrl' => 'https://domain.com/cancel-url',
        ];

        $this->guzzleClient
            ->shouldReceive('post')->once()
            ->with(config('line-pay.reservation-url'), [
                'headers' => [
                    'X-LINE-ChannelId' => config('line-pay.channel-id'),
                    'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'body' => json_encode($params),
                'exceptions' => false,
            ])
            ->andReturn($this->responseMock);

        $response = $this->linePay->processPayment($params);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['request'] == $params, true);
        $this->assertEquals($response['data']['response']['returnCode'], $this->returnCode);
        $this->assertEquals($response['data']['response']['info']['transactionId'], $this->transactionId);
    }

    /**
     * @covers ::processPayment
     */
    public function testProcessPaymentWithValidationError()
    {
        $response = $this->linePay->processPayment([]);

        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals(gettype($response['msg']), 'array');
    }

    /**
     * @covers ::verifyPayment
     */
    public function testVerifyPayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'returnCode' => $this->returnCode,
            ])
        );

        $params = [
            'amount' => '1000',
            'currency' => 'USD',
        ];

        $this->guzzleClient
            ->shouldReceive('post')->once()
            ->with(config('line-pay.detail-url') . '/' . $this->transactionId . '/confirm', [
                'headers' => [
                    'X-LINE-ChannelId' => config('line-pay.channel-id'),
                    'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'body' => json_encode($params),
                'exceptions' => false,
            ])
            ->andReturn($this->responseMock);

        $response = $this->linePay->verifyPayment($this->transactionId, $params);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['request'] == $params, true);
        $this->assertEquals($response['data']['response']['returnCode'], $this->returnCode);
    }

    /**
     * @covers ::verifyPayment
     */
    public function testVerifyPaymentWithValidationError()
    {
        $response = $this->linePay->verifyPayment($this->transactionId, []);

        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals(gettype($response['msg']), 'array');
    }

    /**
     * @covers ::capturePayment
     */
    public function testCapturePayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'returnCode' => $this->returnCode,
            ])
        );

        $params = [
            'amount' => '1000',
            'currency' => 'USD',
        ];

        $this->guzzleClient
            ->shouldReceive('post')->once()
            ->with(config('line-pay.capture-url') . '/' . $this->transactionId . '/capture', [
                'headers' => [
                    'X-LINE-ChannelId' => config('line-pay.channel-id'),
                    'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'body' => json_encode($params),
                'exceptions' => false,
            ])
            ->andReturn($this->responseMock);

        $response = $this->linePay->capturePayment($this->transactionId, $params);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['request'] == $params, true);
        $this->assertEquals($response['data']['response']['returnCode'], $this->returnCode);
    }

    /**
     * @covers ::capturePayment
     */
    public function testCapturePaymentWithValidationError()
    {
        $response = $this->linePay->capturePayment($this->transactionId, []);

        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals(gettype($response['msg']), 'array');
    }

    /**
     * @covers ::voidPayment
     */
    public function testVoidPayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'returnCode' => $this->returnCode,
            ])
        );

        $this->guzzleClient
            ->shouldReceive('post')->once()
            ->with(config('line-pay.capture-url') . '/' . $this->transactionId . '/void', [
                'headers' => [
                    'X-LINE-ChannelId' => config('line-pay.channel-id'),
                    'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'exceptions' => false,
            ])
            ->andReturn($this->responseMock);

        $response = $this->linePay->voidPayment($this->transactionId);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['response']['returnCode'], $this->returnCode);
    }
}
