<?php

namespace Antom\Core\Test\Unit\Observer;

use Antom\Core\Observer\AssignDataObserver;
use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class AssignDataObserverTest extends TestCase
{
    /**
     * @var AssignDataObserver
     */
    private $observer;

    protected function setUp(): void
    {
        $this->observer = new AssignDataObserver();
    }

    /**
     * Test execute with antom_card payment method and card token
     */
    public function testExecuteWithAntomCardAndCardToken(): void
    {
        $additionalData = [AntomConstants::CARD_TOKEN => 'test_card_token_123'];
        
        $paymentMock = $this->createMock(InfoInterface::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);
        
        // Create event with methods
        $event = new class($paymentMock, $dataObject) {
            private $payment;
            private $data;
            
            public function __construct($payment, $data) {
                $this->payment = $payment;
                $this->data = $data;
            }
            
            public function getPaymentModel() {
                return $this->payment;
            }
            
            public function getData($key) {
                return $key === 'data' ? $this->data : null;
            }
        };
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');
        
        $paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(AntomConstants::CARD_TOKEN, 'test_card_token_123');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with non-antom_card payment method
     */
    public function testExecuteWithNonAntomCardPaymentMethod(): void
    {
        $additionalData = [AntomConstants::CARD_TOKEN => 'test_card_token_123'];
        
        $paymentMock = $this->createMock(InfoInterface::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);
        
        $event = new class($paymentMock, $dataObject) {
            private $payment;
            private $data;
            
            public function __construct($payment, $data) {
                $this->payment = $payment;
                $this->data = $data;
            }
            
            public function getPaymentModel() {
                return $this->payment;
            }
            
            public function getData($key) {
                return $key === 'data' ? $this->data : null;
            }
        };
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('other_payment_method');
        
        $paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but no card token
     */
    public function testExecuteWithAntomCardButNoCardToken(): void
    {
        $additionalData = ['other_data' => 'value'];
        
        $paymentMock = $this->createMock(InfoInterface::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);
        
        $event = new class($paymentMock, $dataObject) {
            private $payment;
            private $data;
            
            public function __construct($payment, $data) {
                $this->payment = $payment;
                $this->data = $data;
            }
            
            public function getPaymentModel() {
                return $this->payment;
            }
            
            public function getData($key) {
                return $key === 'data' ? $this->data : null;
            }
        };
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');
        
        $paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but additional data is not array
     */
    public function testExecuteWithAntomCardButAdditionalDataNotArray(): void
    {
        $additionalData = 'not_an_array';
        
        $paymentMock = $this->createMock(InfoInterface::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => $additionalData]);
        
        $event = new class($paymentMock, $dataObject) {
            private $payment;
            private $data;
            
            public function __construct($payment, $data) {
                $this->payment = $payment;
                $this->data = $data;
            }
            
            public function getPaymentModel() {
                return $this->payment;
            }
            
            public function getData($key) {
                return $key === 'data' ? $this->data : null;
            }
        };
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');
        
        $paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->observer->execute($observer);
    }

    /**
     * Test execute with antom_card but additional data is null
     */
    public function testExecuteWithAntomCardButAdditionalDataNull(): void
    {
        $paymentMock = $this->createMock(InfoInterface::class);
        $methodInstanceMock = $this->createMock(MethodInterface::class);
        $dataObject = new DataObject(['additional_data' => null]);
        
        $event = new class($paymentMock, $dataObject) {
            private $payment;
            private $data;
            
            public function __construct($payment, $data) {
                $this->payment = $payment;
                $this->data = $data;
            }
            
            public function getPaymentModel() {
                return $this->payment;
            }
            
            public function getData($key) {
                return $key === 'data' ? $this->data : null;
            }
        };
        
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        
        $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);
        $methodInstanceMock->method('getCode')->willReturn('antom_card');
        
        $paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->observer->execute($observer);
    }
}