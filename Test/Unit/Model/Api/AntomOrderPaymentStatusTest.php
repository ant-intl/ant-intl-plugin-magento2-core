<?php

namespace Antom\Core\Test\Unit\Model\Api;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Api\AntomOrderPaymentStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AntomOrderPaymentStatusTest extends TestCase
{
    /**
     * @var AntomOrderPaymentStatus
     */
    private $model;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var AntomLogger|MockObject
     */
    private $antomLoggerMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->antomLoggerMock = $this->createMock(AntomLogger::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->paymentMock = $this->createMock(Payment::class);

        $this->model = new AntomOrderPaymentStatus(
            $this->orderRepositoryMock,
            $this->antomLoggerMock
        );
    }

    /**
     * Test getOrderPaymentStatus with order not found
     */
    public function testGetOrderPaymentStatusWithOrderNotFound(): void
    {
        $orderId = '999999999';

        $this->orderRepositoryMock->method('get')
            ->with($orderId)
            ->willThrowException(new NoSuchEntityException());

        $this->antomLoggerMock->expects($this->once())
            ->method('error');

        $this->expectException(NoSuchEntityException::class);
        $this->model->getOrderPaymentStatus($orderId);
    }

    /**
     * Test getOrderPaymentStatus with unknown payment method
     */
    public function testGetOrderPaymentStatusWithUnknownPaymentMethod(): void
    {
        $orderId = '100000001';
        $paymentMethod = 'unknown_method';

        $this->orderRepositoryMock->method('get')
            ->with($orderId)
            ->willReturn($this->orderMock);

        $this->orderMock->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->method('getMethod')
            ->willReturn($paymentMethod);

        $result = $this->model->getOrderPaymentStatus($orderId);
        $expected = json_encode([
            AntomConstants::PAYMENT_STATUS => "error",
            "message" => "error"
        ]);

        $this->assertEquals($expected, $result);
    }
}