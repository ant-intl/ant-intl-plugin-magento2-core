<?php

namespace Antom\Core\Test\Unit\Observer;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Observer\PaymentAssignDataObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use PHPUnit\Framework\TestCase;

class PaymentAssignDataObserverTest extends TestCase
{
    /**
     * @var PaymentAssignDataObserver
     */
    private $observer;

    /**
     * @var PaymentCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentCollectionFactoryMock;

    /**
     * @var AntomLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->paymentCollectionFactoryMock = $this->createMock(PaymentCollectionFactory::class);
        $this->loggerMock = $this->createMock(AntomLogger::class);

        $this->observer = new PaymentAssignDataObserver(
            $this->paymentCollectionFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test execute with antom_card payment method and payment request ID
     */
    public function testExecuteWithAntomCardAndPaymentRequestId(): void
    {
        $paymentRequestId = 'test_payment_request_123';
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        // Create event with methods
        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;

            /**
             * @var array
             */
            private $data;

            /**
             * Constructor
             *
             * @param Payment $payment
             * @param $data
             */
            public function __construct(Payment $payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData(string $key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        // Mock empty collection for no existing orders
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->paymentCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $calls = [];

        $this->loggerMock->expects($this->exactly(2))
            ->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$calls) {
                // 捕获每次调用参数
                $calls[] = ['message' => $message, 'context' => $context];
                return true; // 根据需要返回 true/false
            });

        $this->observer->execute($observer);

        $this->assertCount(2, $calls);
        $this->assertEquals('=== Processing Antom payment assignment ===', $calls[0]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[0]['context']);
        $this->assertEquals('Payment request validated and assigned', $calls[1]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[1]['context']);
    }

    /**
     * Test execute with non-antom_card payment method
     */
    public function testExecuteWithNonAntomCardPaymentMethod(): void
    {
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => 'test_payment_request_123'];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;

            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param $payment
             * @param $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('other_payment_method');

        $paymentMock->expects($this->never())
            ->method('setData');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but no payment request ID
     */
    public function testExecuteWithAntomCardButNoPaymentRequestId(): void
    {
        $additionalData = ['other_data' => 'value'];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;

            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param Payment $payment
             * @param DataObject $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        $paymentMock->expects($this->never())
            ->method('setData');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but additional data is not array
     */
    public function testExecuteWithAntomCardButAdditionalDataNotArray(): void
    {
        $additionalData = 'not_an_array';

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param Payment $payment
             * @param DataObject $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        $paymentMock->expects($this->never())
            ->method('setData');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but additional data is null
     */
    public function testExecuteWithAntomCardButAdditionalDataNull(): void
    {
        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => null]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param Payment $payment
             * @param DataObject $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        $paymentMock->expects($this->never())
            ->method('setData');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with existing successful order should throw exception
     */
    public function testExecuteWithExistingSuccessfulOrder(): void
    {
        $paymentRequestId = 'existing_successful_payment_123';
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param Payment $payment
             * @param DataObject $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        // Mock order and payment
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(123);
        $orderMock->method('getIncrementId')->willReturn('100000123');
        $orderMock->method('getState')->willReturn('processing');
        $orderMock->method('getStatus')->willReturn('processing');

        $paymentModelMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentModelMock->method('getOrder')->willReturn($orderMock);

        // Mock collection with existing order
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$paymentModelMock]));

        $this->paymentCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $this->loggerMock->expects($this->once())
            ->method('addAntomWarning')
            ->with('Payment rejected due to existing successful order', $this->equalTo([
                'order_id' => 123,
                'increment_id' => '100000123',
                'order_state' => 'processing',
                'order_status' => 'processing',
                'payment_request_id' => $paymentRequestId,
                'rejection_reason' => 'duplicate_successful_payment'
            ]));

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Payment rejected: Order #100000123 with payment_request_id '
            . 'existing_successful_payment_123 is already processing. Duplicate payment not allowed.');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with existing pending order that can be replaced
     */
    public function testExecuteWithExistingPendingOrder(): void
    {
        $paymentRequestId = 'existing_pending_payment_123';
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param $payment
             * @param $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return DataObject|null
             */
            public function getData(string $key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        // Mock order and payment
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(124);
        $orderMock->method('getIncrementId')->willReturn('100000124');
        $orderMock->method('getState')->willReturn('pending_payment');
        $orderMock->method('getStatus')->willReturn('pending_payment');
        $orderMock->method('canCancel')->willReturn(true);
        $orderMock->method('addStatusHistoryComment')->willReturnSelf();
        $orderMock->method('save')->willReturnSelf();

        $paymentModelMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentModelMock->method('getOrder')->willReturn($orderMock);

        // Mock collection with existing order
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$paymentModelMock]));

        $this->paymentCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $calls = [];
        $this->loggerMock->expects($this->exactly(4))
            ->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$calls, $paymentRequestId) {
                $calls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $paymentMock->expects($this->once())
            ->method('setData')
            ->with('antom_payment_request_id', $paymentRequestId);

        $this->observer->execute($observer);

        // After execution, verify the calls
        $this->assertCount(4, $calls);
        $this->assertEquals('=== Processing Antom payment assignment ===', $calls[0]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[0]['context']);

        $this->assertEquals('Found existing order for payment request', $calls[1]['message']);
        $this->assertEquals([
            'order_id' => 124,
            'increment_id' => '100000124',
            'current_state' => 'pending_payment',
            'current_status' => 'pending_payment',
            'payment_request_id' => $paymentRequestId
        ], $calls[1]['context']);

        $this->assertEquals('Replaceable order closed successfully', $calls[2]['message']);
        $this->assertEquals([
            'order_id' => 124,
            'increment_id' => '100000124',
            'payment_request_id' => $paymentRequestId
        ], $calls[2]['context']);

        $this->assertEquals('Payment request validated and assigned', $calls[3]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[3]['context']);
    }

    /**
     * Test order that cannot be cancelled normally falls back to setState/setStatus
     * // covers lines 164-166
     */
    public function testExecuteWithOrderCannotBeCancelledNormally(): void
    {
        $paymentRequestId = 'uncancellable_order_123';
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param $payment
             * @param $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        // Mock order that cannot be cancelled normally
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(125);
        $orderMock->method('getIncrementId')->willReturn('100000125');
        $orderMock->method('getState')->willReturn('pending_payment');
        $orderMock->method('getStatus')->willReturn('pending_payment');
        $orderMock->method('canCancel')->willReturn(false); // Cannot cancel normally
        $orderMock->method('addStatusHistoryComment')->willReturnSelf();
        $orderMock->method('save')->willReturnSelf();

        // Expect setState and setStatus to be called (lines 164-165)
        $orderMock->expects($this->once())
            ->method('setState')
            ->with(\Magento\Sales\Model\Order::STATE_CANCELED);

        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with(\Magento\Sales\Model\Order::STATE_CANCELED);

        $orderMock->expects($this->once())
            ->method('addStatusHistoryComment')
            ->with($this->stringContains('Order replaced due to new payment attempt'), Order::STATE_CANCELED);

        $paymentModelMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentModelMock->method('getOrder')->willReturn($orderMock);

        // Mock collection with existing order
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$paymentModelMock]));

        $this->paymentCollectionFactoryMock->method('create')->willReturn($collectionMock);

        $paymentMock->expects($this->once())
            ->method('setData')
            ->with('antom_payment_request_id', $paymentRequestId);

        $calls = [];
        $this->loggerMock->expects($this->exactly(4))
            ->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$calls, $paymentRequestId) {
                $calls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $this->observer->execute($observer);

        // After execution, verify the calls
        $this->assertCount(4, $calls);
        $this->assertEquals('=== Processing Antom payment assignment ===', $calls[0]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[0]['context']);

        $this->assertEquals('Found existing order for payment request', $calls[1]['message']);
        $this->assertEquals([
            'order_id' => 125,
            'increment_id' => '100000125',
            'current_state' => 'pending_payment',
            'current_status' => 'pending_payment',
            'payment_request_id' => $paymentRequestId
        ], $calls[1]['context']);

        $this->assertEquals('Replaceable order closed successfully', $calls[2]['message']);
        $this->assertEquals([
            'order_id' => 125,
            'increment_id' => '100000125',
            'payment_request_id' => $paymentRequestId
        ], $calls[2]['context']);

        $this->assertEquals('Payment request validated and assigned', $calls[3]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[3]['context']);
    }

    /**
     * Test exception handling when closing replaceable order fails
     *  // covers lines 178-186
     */
    public function testExecuteWithExceptionWhenClosingOrder(): void
    {
        $paymentRequestId = 'exception_order_123';
        $additionalData = [AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId];

        $paymentMock = $this->createMock(Payment::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);

        $event = new class($paymentMock, $dataObject) {
            /**
             * @var Payment
             */
            private $payment;
            /**
             * @var DataObject
             */
            private DataObject $data;

            /**
             * @param Payment $payment
             * @param DataObject $data
             */
            public function __construct($payment, $data)
            {
                $this->payment = $payment;
                $this->data = $data;
            }

            /**
             * @return Payment
             */
            public function getPaymentModel()
            {
                return $this->payment;
            }

            /**
             * @param $key
             * @return array|null
             */
            public function getData($key)
            {
                return $key === 'data' ? $this->data : null;
            }
        };

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');

        // Create a spy to track if the exception handling code was executed
        $exceptionThrown = false;

        // Mock order that throws exception when trying to save
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(126);
        $orderMock->method('getIncrementId')->willReturn('100000126');
        $orderMock->method('getState')->willReturn('pending_payment');
        $orderMock->method('getStatus')->willReturn('pending_payment');
        $orderMock->method('canCancel')->willReturn(true);
        $orderMock->method('addStatusHistoryComment')->willReturnSelf();
        $orderMock->method('cancel')->willReturnSelf();

        // Make save() throw an exception to trigger the catch block
        $orderMock->method('save')
            ->willReturnCallback(function () use (&$exceptionThrown) {
                $exceptionThrown = true;
                throw new \Exception('Database connection failed');
            });

        $paymentModelMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentModelMock->method('getOrder')->willReturn($orderMock);

        // Mock collection with existing order
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([$paymentModelMock]));

        $this->paymentCollectionFactoryMock->method('create')->willReturn($collectionMock);
        $capturedCalls = [];

        // Expect error and warning logs (lines 179-186) - these prove the exception was caught
        $this->loggerMock->expects($this->once())
            ->method('error')
//            ->with('Failed to close replaceable order', $this->equalTo([
//                'order_id' => 126,
//                'payment_request_id' => $paymentRequestId,
//                'error' => 'Database connection failed'
//            ]))
            ->willReturnCallback(function ($message, $context)
            use (&$capturedCalls, $orderMock, $paymentRequestId, $exceptionThrown) {
                $capturedCalls['error'] = ['message' => $message, 'context' => $context];
                return null;
            });

        $this->loggerMock->expects($this->once())
            ->method('addAntomWarning')
            ->willReturnCallback(function($message) use (&$capturedCalls) {
                $capturedCalls['warning'] = ['message' => $message];
                return true;
            });

        $calls = [];
        // Should have 3 info logs (processing, found order, success) but NOT "closed successfully"
        $this->loggerMock->expects($this->exactly(3))
            ->method('addAntomInfoLog')
            ->willReturnCallback(function($message, $context = []) use (&$calls, $paymentRequestId) {
                $calls[] = ['message' => $message, 'context' => $context];
                return true;
            });


        $paymentMock->expects($this->once())
            ->method('setData')
            ->with('antom_payment_request_id', $paymentRequestId);

        // Execute and then verify the exception was actually triggered
        $this->observer->execute($observer);

        // Assert that the exception handling code was actually executed
        $this->assertTrue($exceptionThrown, 'Exception should have been thrown and caught in closeReplaceableOrder');

        // After execution, verify the calls
        $this->assertCount(3, $calls);
        $this->assertEquals('=== Processing Antom payment assignment ===', $calls[0]['message']);
        $this->assertEquals(['payment_request_id' => $paymentRequestId], $calls[0]['context']);

        $this->assertEquals('Found existing order for payment request', $calls[1]['message']);
        $this->assertEquals([
            'order_id' => 126,
            'increment_id' => '100000126',
            'current_state' => 'pending_payment',
            'current_status' => 'pending_payment',
            'payment_request_id' => $paymentRequestId
        ], $calls[1]['context']);

        $this->assertEquals('Payment request validated and assigned', $calls[2]['message']);
        $this->assertEquals([
            'payment_request_id' => $paymentRequestId
        ], $calls[2]['context']);

        $this->assertArrayHasKey('error', $capturedCalls);
        $this->assertArrayHasKey('warning', $capturedCalls);
        $this->assertEquals('Failed to close replaceable order', $capturedCalls['error']['message']);
        $this->assertEquals('Continuing with new payment despite closure failure', $capturedCalls['warning']['message']);
    }
}
