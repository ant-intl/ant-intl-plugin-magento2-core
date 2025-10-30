<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsPaymentRequestIdBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\PaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPaymentRequestIdBuilder
 */
class AmsPaymentRequestIdBuilderTest extends TestCase
{
    /**
     * @var AmsPaymentRequestIdBuilder
     */
    private $builder;

    /**
     * @var AntomLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private $orderMock;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loggerMock = $this->createMock(AntomLogger::class);
        $this->builder = new AmsPaymentRequestIdBuilder($this->loggerMock);
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);
    }

    public function testBuildPaymentRequestId(): void
    {
        $orderId = '100000001';
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with($this->equalTo(AntomConstants::PAYMENT_REQUEST_ID), $this->stringContains($orderId));

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_REQUEST_ID, $result);
        $this->assertStringContainsString($orderId, $result[AntomConstants::PAYMENT_REQUEST_ID]);
        $this->assertStringContainsString((string)$storeId, $result[AntomConstants::PAYMENT_REQUEST_ID]);
    }

    public function testBuildWithDifferentOrderId(): void
    {
        $orderId = '100000002';
        $storeId = 2;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AntomConstants::PAYMENT_REQUEST_ID, $this->stringContains($orderId));

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertStringContainsString($orderId, $result[AntomConstants::PAYMENT_REQUEST_ID]);
        $this->assertStringContainsString('2', $result[AntomConstants::PAYMENT_REQUEST_ID]);
    }

    public function testBuildWithLongOrderId(): void
    {
        $orderId = '999999999999999';
        $storeId = 99;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AntomConstants::PAYMENT_REQUEST_ID, $this->stringContains($orderId));

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertStringContainsString($orderId, $result[AntomConstants::PAYMENT_REQUEST_ID]);
        $this->assertStringContainsString('99', $result[AntomConstants::PAYMENT_REQUEST_ID]);
    }

    public function testBuildWithRandomNumberGeneration(): void
    {
        $orderId = '100000003';
        $storeId = 3;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AntomConstants::PAYMENT_REQUEST_ID, $this->stringContains($orderId));

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_REQUEST_ID, $result);
        $this->assertNotEmpty($result[AntomConstants::PAYMENT_REQUEST_ID]);
    }

    public function testBuildWithRandomIntException(): void
    {
        $orderId = '100000004';
        $storeId = 4;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->loggerMock->expects($this->never())
            ->method('addAntomWarning');

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AntomConstants::PAYMENT_REQUEST_ID, $this->stringContains($orderId));

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_REQUEST_ID, $result);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $orderId = '100000005';
        $storeId = 5;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getOrderIncrementId')->willReturn($orderId);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PAYMENT_REQUEST_ID, $result);
    }
}