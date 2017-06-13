<?php
namespace Rohit\Tests;

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

    public function setUp()
    {
        parent::setUp();

        $this->guzzleClient = m::mock(Client::class);
        $this->linePay      = m::mock(LinePay::class, [$this->guzzleClient])->makePartial();
        $this->responseMock = m::mock(Response::class);
        $this->streamMock   = m::mock(Stream::class);

        $this->responseMock->shouldReceive('getStatusCode')
            ->andReturn(200);

        $this->responseMock->shouldReceive('getBody')
            ->andReturn($this->streamMock);
    }

    /**
     * @covers ::processPayment
     */
    public function testProcessPayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'info' => [
                    'transactionId' => '12345',
                    'paymentUrl' => [
                        'web' => 'paymentUrl',
                    ],
                ],
            ])
        );

        $params = [
            'productName' => 'Name of Product you want user to pay for',
            'amount' => 'Amount of the product',
            'currency' => 'Currency Code eg: USD',
            'orderId' => 'Unique Order Id',
            'confirmUrl' => 'Url to which Line will redirect after successful of payment',
            'cancelUrl' => 'Url to which Line will redirect when user cancel the payment',
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
        $this->assertEquals($response['data']['transaction-id'], '12345');
    }

    /**
     * @covers ::verifyPayment
     */
    public function testVerifyPayment()
    {
        $this->streamMock->shouldReceive('getContents')
            ->andReturn(json_encode([
                'returnCode' => '0000',
            ])
        );

        $params = [
            'amount' => 'Amount of the Order',
            'currency' => 'Currency Code eg: USD',
        ];

        $transactionId = '12345';

        $this->guzzleClient
            ->shouldReceive('post')->once()
            ->with(config('line-pay.detail-url') . '/' . $transactionId . '/confirm', [
                'headers' => [
                    'X-LINE-ChannelId' => config('line-pay.channel-id'),
                    'X-LINE-ChannelSecret' => config('line-pay.channel-secret'),
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'body' => json_encode($params),
                'exceptions' => false,
            ])
            ->andReturn($this->responseMock);

        $response = $this->linePay->verifyPayment('12345', $params);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['data']['transaction-id'], $transactionId);
    }
}
