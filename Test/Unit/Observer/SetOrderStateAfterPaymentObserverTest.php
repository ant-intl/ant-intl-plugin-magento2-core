<?php

namespace Antom\Core\Test\Unit\Observer;

use Antom\Core\Observer\SetOrderStateAfterPaymentObserver;
use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\StatusResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetOrderStateAfterPaymentObserverTest extends TestCase
{
    /**
     * @var SetOrderStateAfterPaymentObserver
     */
    private $observer;

    /**
     * @var StatusResolver|MockObject
     */
    private $statusResolverMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        $this->statusResolverMock = $this->createMock(StatusResolver::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->createMock(Event::class);
        $this->paymentMock = $this->createMock(Payment::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->observer = new SetOrderStateAfterPaymentObserver(
            $this->statusResolverMock,
            $this->orderRepositoryMock
        );

        $this->observerMock->method('getData')->with('payment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getOrder')->willReturn($this->orderMock);
    }

    /**
     * Test execute with initiated payment status
     */
    public function testExecuteWithInitiatedPaymentStatus(): void
    {
        $status = 'pending_payment';
        $resultMessage = 'Payment initiated, waiting for customer action';

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED],
                [AntomConstants::RESULT_MESSAGE, $resultMessage]
            ]);

        $this->statusResolverMock->method('getOrderStatusByState')
            ->with($this->orderMock, Order::STATE_PENDING_PAYMENT)
            ->willReturn($status);

        $this->orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_PENDING_PAYMENT);
        $this->orderMock->expects($this->once())
            ->method('setStatus')
            ->with($status);
        $this->orderMock->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with(__($resultMessage), $status);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->orderMock);

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute with non-initiated payment status
     */
    public function testExecuteWithNonInitiatedPaymentStatus(): void
    {
        $this->paymentMock->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_STATUS, 'SUCCESS'],
                [AntomConstants::RESULT_MESSAGE, 'Payment successful']
            ]);

        $this->statusResolverMock->expects($this->never())
            ->method('getOrderStatusByState');
        $this->orderMock->expects($this->never())
            ->method('setState');
        $this->orderMock->expects($this->never())
            ->method('setStatus');
        $this->orderMock->expects($this->never())
            ->method('addCommentToStatusHistory');
        $this->orderRepositoryMock->expects($this->never())
            ->method('save');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute with empty payment status
     */
    public function testExecuteWithEmptyPaymentStatus(): void
    {
        $this->paymentMock->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_STATUS, ''],
                [AntomConstants::RESULT_MESSAGE, '']
            ]);

        $this->statusResolverMock->expects($this->never())
            ->method('getOrderStatusByState');
        $this->orderMock->expects($this->never())
            ->method('setState');
        $this->orderMock->expects($this->never())
            ->method('setStatus');
        $this->orderMock->expects($this->never())
            ->method('addCommentToStatusHistory');
        $this->orderRepositoryMock->expects($this->never())
            ->method('save');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute with null payment status
     */
    public function testExecuteWithNullPaymentStatus(): void
    {
        $this->paymentMock->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_STATUS, null],
                [AntomConstants::RESULT_MESSAGE, null]
            ]);

        $this->statusResolverMock->expects($this->never())
            ->method('getOrderStatusByState');
        $this->orderMock->expects($this->never())
            ->method('setState');
        $this->orderMock->expects($this->never())
            ->method('setStatus');
        $this->orderMock->expects($this->never())
            ->method('addCommentToStatusHistory');
        $this->orderRepositoryMock->expects($this->never())
            ->method('save');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute with missing result message
     */
    public function testExecuteWithMissingResultMessage(): void
    {
        $status = 'pending_payment';

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED],
                [AntomConstants::RESULT_MESSAGE, null]
            ]);

        $this->statusResolverMock->method('getOrderStatusByState')
            ->with($this->orderMock, Order::STATE_PENDING_PAYMENT)
            ->willReturn($status);

        $this->orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_PENDING_PAYMENT);
        $this->orderMock->expects($this->once())
            ->method('setStatus')
            ->with($status);
        $this->orderMock->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with(__(''), $status);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->orderMock);

        $this->observer->execute($this->observerMock);
    }
}