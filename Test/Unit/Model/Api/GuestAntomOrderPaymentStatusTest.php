<?php

namespace Antom\Core\Test\Unit\Model\Api;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Api\GuestAntomOrderPaymentStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuestAntomOrderPaymentStatusTest extends TestCase
{
    /**
     * @var GuestAntomOrderPaymentStatus
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
     * @var MaskedQuoteIdToQuoteIdInterface|MockObject
     */
    private $maskedQuoteIdToQuoteIdMock;

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
        $this->maskedQuoteIdToQuoteIdMock = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->paymentMock = $this->createMock(Payment::class);

        $this->model = new GuestAntomOrderPaymentStatus(
            $this->orderRepositoryMock,
            $this->antomLoggerMock,
            $this->maskedQuoteIdToQuoteIdMock
        );
    }

    /**
     * Test getOrderPaymentStatus with cart not found
     */
    public function testGetOrderPaymentStatusWithCartNotFound(): void
    {
        $orderId = '100000001';
        $cartId = 'invalid_cart';

        $this->maskedQuoteIdToQuoteIdMock->method('execute')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException());

        $this->antomLoggerMock->expects($this->once())
            ->method('error');

        $this->expectException(NotFoundException::class);
        $this->model->getOrderPaymentStatus($orderId, $cartId);
    }

    /**
     * Test getOrderPaymentStatus with order not matching quote
     */
    public function testGetOrderPaymentStatusWithOrderNotMatchingQuote(): void
    {
        $orderId = '100000002';
        $cartId = 'masked_cart_123';
        $quoteId = 123;
        $orderQuoteId = 999;

        $this->maskedQuoteIdToQuoteIdMock->method('execute')
            ->with($cartId)
            ->willReturn($quoteId);

        $this->orderRepositoryMock->method('get')
            ->with($orderId)
            ->willReturn($this->orderMock);

        $this->orderMock->method('getQuoteId')
            ->willReturn($orderQuoteId);

        $this->antomLoggerMock->expects($this->once())
            ->method('error');

        $this->expectException(NotFoundException::class);
        $this->model->getOrderPaymentStatus($orderId, $cartId);
    }

    /**
     * Test getOrderPaymentStatus with unknown payment method
     */
    public function testGetOrderPaymentStatusWithUnknownPaymentMethod(): void
    {
        $orderId = '100000003';
        $cartId = 'masked_cart_456';
        $quoteId = 456;
        $paymentMethod = 'unknown_method';

        $this->maskedQuoteIdToQuoteIdMock->method('execute')
            ->with($cartId)
            ->willReturn($quoteId);

        $this->orderRepositoryMock->method('get')
            ->with($orderId)
            ->willReturn($this->orderMock);

        $this->orderMock->method('getQuoteId')
            ->willReturn($quoteId);
        $this->orderMock->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->method('getMethod')
            ->willReturn($paymentMethod);

        $result = $this->model->getOrderPaymentStatus($orderId, $cartId);
        $expected = json_encode([
            AntomConstants::PAYMENT_STATUS => "error",
            "message" => "error"
        ]);

        $this->assertEquals($expected, $result);
    }
}