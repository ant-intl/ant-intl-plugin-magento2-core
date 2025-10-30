<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsPaymentAmountBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPaymentAmountBuilder
 */
class AmsPaymentAmountBuilderTest extends TestCase
{
    /**
     * @var AmsPaymentAmountBuilder
     */
    private $builder;

    /**
     * @var RequestHelper|MockObject
     */
    private $helperMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->helperMock = $this->createMock(RequestHelper::class);
        $this->builder = new AmsPaymentAmountBuilder($this->helperMock);
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
    }

    public function testBuildPaymentAmountWithUSD(): void
    {
        $expectedAmount = [
            AntomConstants::CURRENCY => 'USD',
            AntomConstants::VALUE => '1000'
        ];

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->helperMock->expects($this->once())
            ->method('getOrderAmount')
            ->with($this->orderMock)
            ->willReturn($expectedAmount);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_AMOUNT, $result);
        $this->assertEquals($expectedAmount, $result[AntomConstants::PAYMENT_AMOUNT]);
    }

    public function testBuildPaymentAmountWithEUR(): void
    {
        $expectedAmount = [
            AntomConstants::CURRENCY => 'EUR',
            AntomConstants::VALUE => '2500'
        ];

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->helperMock->method('getOrderAmount')->with($this->orderMock)->willReturn($expectedAmount);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('EUR', $result[AntomConstants::PAYMENT_AMOUNT][AntomConstants::CURRENCY]);
        $this->assertEquals('2500', $result[AntomConstants::PAYMENT_AMOUNT][AntomConstants::VALUE]);
    }

    public function testBuildPaymentAmountWithJPY(): void
    {
        $expectedAmount = [
            AntomConstants::CURRENCY => 'JPY',
            AntomConstants::VALUE => '10000'
        ];

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->helperMock->method('getOrderAmount')->with($this->orderMock)->willReturn($expectedAmount);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('JPY', $result[AntomConstants::PAYMENT_AMOUNT][AntomConstants::CURRENCY]);
        $this->assertEquals('10000', $result[AntomConstants::PAYMENT_AMOUNT][AntomConstants::VALUE]);
    }

    public function testBuildPaymentAmountWithZeroValue(): void
    {
        $expectedAmount = [
            AntomConstants::CURRENCY => 'USD',
            AntomConstants::VALUE => '0'
        ];

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->helperMock->method('getOrderAmount')->with($this->orderMock)->willReturn($expectedAmount);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('0', $result[AntomConstants::PAYMENT_AMOUNT][AntomConstants::VALUE]);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $expectedAmount = [
            AntomConstants::CURRENCY => 'GBP',
            AntomConstants::VALUE => '750'
        ];

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->helperMock->method('getOrderAmount')->with($this->orderMock)->willReturn($expectedAmount);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PAYMENT_AMOUNT, $result);
    }
}