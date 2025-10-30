<?php

namespace Antom\Core\Test\Unit\Gateway\Http;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Gateway\Http\TransferFactory;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


/**
 * Unit test for TransferFactory
 */
class TransferFactoryTest extends TestCase
{
    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var TransferBuilder|MockObject
     */
    private $transferBuilderMock;

    /**
     * @var TransferInterface|MockObject
     */
    private $transferMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transferBuilderMock = $this->createMock(TransferBuilder::class);
        $this->transferMock = $this->createMock(TransferInterface::class);
        
        $this->transferFactory = new TransferFactory($this->transferBuilderMock);
    }

    public function testCreateWithCompleteData(): void
    {
        $request = [
            AntomConstants::HEADERS => [
                'Content-Type' => 'application/json',
                'Client-Id' => 'test_client_id'
            ],
            AntomConstants::METHOD => 'POST',
            AntomConstants::URI => '/ams/api/v1/payments/pay',
            AntomConstants::CLIENT_CONFIG => [
                'gateway_url' => 'https://test.alipay.com',
                'client_id' => 'test_client_id'
            ],
            AntomConstants::ENV => [
                'terminalType' => 'WEB',
                'clientIp' => '127.0.0.1'
            ],
            AntomConstants::PAYMENT_AMOUNT => [
                'currency' => 'USD',
                'value' => '1000'
            ],
            AntomConstants::PAYMENT_METHOD => [
                'paymentMethodType' => 'CARD'
            ],
            AntomConstants::PAYMENT_REQUEST_ID => 'request123',
            AntomConstants::ORDER => [
                'referenceOrderId' => '100000001',
                'orderDescription' => 'Test Order'
            ],
            AntomConstants::PRODUCT_CODE => 'CASHIER_PAYMENT',
            AntomConstants::SETTLEMENT_STRATEGY => [
                'settlementCurrency' => 'USD'
            ],
            AntomConstants::PAYMENT_REDIRECT_URL => 'https://example.com/redirect',
            AntomConstants::PAYMENT_FACTOR => [
                'isAuthorization' => true
            ],
            AntomConstants::PAYMENT_NOTIFY_URL => 'https://example.com/notify'
        ];

        $this->transferBuilderMock->expects($this->once())
            ->method('setHeaders')
            ->with($request[AntomConstants::HEADERS])
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('setMethod')
            ->with($request[AntomConstants::METHOD])
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('setUri')
            ->with($request[AntomConstants::URI])
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('setClientConfig')
            ->with($request[AntomConstants::CLIENT_CONFIG])
            ->willReturnSelf();

        $expectedBody = [
            AntomConstants::ENV => $request[AntomConstants::ENV],
            AntomConstants::PAYMENT_AMOUNT => $request[AntomConstants::PAYMENT_AMOUNT],
            AntomConstants::PAYMENT_METHOD => $request[AntomConstants::PAYMENT_METHOD],
            AntomConstants::PAYMENT_REQUEST_ID => $request[AntomConstants::PAYMENT_REQUEST_ID],
            AntomConstants::ORDER => $request[AntomConstants::ORDER],
            AntomConstants::PRODUCT_CODE => $request[AntomConstants::PRODUCT_CODE],
            AntomConstants::SETTLEMENT_STRATEGY => $request[AntomConstants::SETTLEMENT_STRATEGY],
            AntomConstants::PAYMENT_REDIRECT_URL => $request[AntomConstants::PAYMENT_REDIRECT_URL],
            AntomConstants::PAYMENT_FACTOR => $request[AntomConstants::PAYMENT_FACTOR],
            AntomConstants::PAYMENT_NOTIFY_URL => $request[AntomConstants::PAYMENT_NOTIFY_URL]
        ];

        $this->transferBuilderMock->expects($this->once())
            ->method('setBody')
            ->with($expectedBody)
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($this->transferMock);

        $result = $this->transferFactory->create($request);
        $this->assertInstanceOf(TransferInterface::class, $result);
    }

    public function testCreateWithoutOptionalFields(): void
    {
        $request = [
            AntomConstants::HEADERS => [
                'Content-Type' => 'application/json'
            ],
            AntomConstants::METHOD => 'POST',
            AntomConstants::URI => '/ams/api/v1/payments/pay',
            AntomConstants::CLIENT_CONFIG => [
                'gateway_url' => 'https://test.alipay.com'
            ],
            AntomConstants::ENV => [
                'terminalType' => 'WEB'
            ],
            AntomConstants::PAYMENT_AMOUNT => [
                'currency' => 'USD',
                'value' => '1000'
            ],
            AntomConstants::PAYMENT_METHOD => [
                'paymentMethodType' => 'ALIPAY_CN'
            ],
            AntomConstants::PAYMENT_REQUEST_ID => 'request123',
            AntomConstants::ORDER => [
                'referenceOrderId' => '100000001'
            ],
            AntomConstants::PRODUCT_CODE => 'CASHIER_PAYMENT',
            AntomConstants::SETTLEMENT_STRATEGY => [
                'settlementCurrency' => 'USD'
            ],
            AntomConstants::PAYMENT_REDIRECT_URL => 'https://example.com/redirect'
        ];

        $this->transferBuilderMock->expects($this->once())
            ->method('setBody')
            ->with($this->equalTo([
                AntomConstants::ENV => $request[AntomConstants::ENV],
                AntomConstants::PAYMENT_AMOUNT => $request[AntomConstants::PAYMENT_AMOUNT],
                AntomConstants::PAYMENT_METHOD => $request[AntomConstants::PAYMENT_METHOD],
                AntomConstants::PAYMENT_REQUEST_ID => $request[AntomConstants::PAYMENT_REQUEST_ID],
                AntomConstants::ORDER => $request[AntomConstants::ORDER],
                AntomConstants::PRODUCT_CODE => $request[AntomConstants::PRODUCT_CODE],
                AntomConstants::SETTLEMENT_STRATEGY => $request[AntomConstants::SETTLEMENT_STRATEGY],
                AntomConstants::PAYMENT_REDIRECT_URL => $request[AntomConstants::PAYMENT_REDIRECT_URL]
            ]))
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($this->transferMock);

        $result = $this->transferFactory->create($request);
        $this->assertInstanceOf(TransferInterface::class, $result);
    }

    public function testCreateWithCardPaymentRequiresPaymentFactor(): void
    {
        $request = [
            AntomConstants::HEADERS => ['Content-Type' => 'application/json'],
            AntomConstants::METHOD => 'POST',
            AntomConstants::URI => '/ams/api/v1/payments/pay',
            AntomConstants::CLIENT_CONFIG => ['gateway_url' => 'https://test.alipay.com'],
            AntomConstants::ENV => ['terminalType' => 'WEB'],
            AntomConstants::PAYMENT_AMOUNT => ['currency' => 'USD', 'value' => '1000'],
            AntomConstants::PAYMENT_METHOD => ['paymentMethodType' => 'CARD'],
            AntomConstants::PAYMENT_REQUEST_ID => 'request123',
            AntomConstants::ORDER => ['referenceOrderId' => '100000001'],
            AntomConstants::PRODUCT_CODE => 'CASHIER_PAYMENT',
            AntomConstants::SETTLEMENT_STRATEGY => ['settlementCurrency' => 'USD'],
            AntomConstants::PAYMENT_REDIRECT_URL => 'https://example.com/redirect',
            AntomConstants::PAYMENT_FACTOR => ['isAuthorization' => true]
        ];

        $this->transferBuilderMock->expects($this->once())
            ->method('setHeaders')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('setMethod')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('setUri')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('setClientConfig')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('setBody')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($this->transferMock);

        $result = $this->transferFactory->create($request);
        $this->assertInstanceOf(TransferInterface::class, $result);
    }

    public function testCreateThrowsExceptionWhenRequiredFieldsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = [
            AntomConstants::METHOD => 'POST',
            AntomConstants::URI => '/ams/api/v1/payments/pay'
        ];

        $this->transferFactory->create($request);
    }
}