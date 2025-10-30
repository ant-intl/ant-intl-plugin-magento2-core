<?php

namespace Antom\Core\Test\Unit\Observer;

use Antom\Core\Observer\QuoteStatusObserver;
use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class QuoteStatusObserverTest extends TestCase
{
    /**
     * @var QuoteStatusObserver
     */
    private $observer;

    protected function setUp(): void
    {
        $this->observer = new QuoteStatusObserver();
    }

    /**
     * Test execute with initiated payment status and redirect action
     */
    public function testExecuteWithInitiatedStatusAndRedirectAction(): void
    {
        $paymentAction = json_encode([AntomConstants::ACTION => AntomConstants::REDIRECT]);
        
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, $paymentAction],
                [AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED]
            ]);

        $quote->expects($this->once())
            ->method('setIsActive')
            ->with(true);

        $this->observer->execute($observer);
    }

    /**
     * Test execute with fail payment status
     */
    public function testExecuteWithFailStatus(): void
    {
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, null],
                [AntomConstants::PAYMENT_STATUS, AntomConstants::FAIL]
            ]);

        $quote->expects($this->once())
            ->method('setIsActive')
            ->with(true);

        $this->observer->execute($observer);
    }

    /**
     * Test execute with non-initiated and non-fail status
     */
    public function testExecuteWithNonInitiatedNonFailStatus(): void
    {
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, null],
                [AntomConstants::PAYMENT_STATUS, 'SUCCESS']
            ]);

        $quote->expects($this->never())
            ->method('setIsActive');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with initiated status but non-redirect action
     */
    public function testExecuteWithInitiatedStatusButNonRedirectAction(): void
    {
        $paymentAction = json_encode([AntomConstants::ACTION => 'OTHER_ACTION']);
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, $paymentAction],
                [AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED]
            ]);

        $quote->expects($this->never())
            ->method('setIsActive');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with empty payment action
     */
    public function testExecuteWithEmptyPaymentAction(): void
    {
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, ''],
                [AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED]
            ]);

        $quote->expects($this->never())
            ->method('setIsActive');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with empty payment status
     */
    public function testExecuteWithEmptyPaymentStatus(): void
    {
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, null],
                [AntomConstants::PAYMENT_STATUS, '']
            ]);

        $quote->expects($this->never())
            ->method('setIsActive');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with null payment action and status
     */
    public function testExecuteWithNullPaymentActionAndStatus(): void
    {
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->getMock();
        
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getAdditionalInformation')
            ->willReturnMap([
                [AntomConstants::PAYMENT_ACTION, null],
                [AntomConstants::PAYMENT_STATUS, null]
            ]);

        $quote->expects($this->never())
            ->method('setIsActive');

        $this->observer->execute($observer);
    }
}