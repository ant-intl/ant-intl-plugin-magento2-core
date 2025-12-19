<?php

namespace Antom\Core\Test\Unit\Plugin;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Plugin\ToOrderPaymentPlugin;
use Antom\Core\Logger\AntomLogger;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use PHPUnit\Framework\TestCase;

class ToOrderPaymentPluginTest extends TestCase
{
    /**
     * @var ToOrderPaymentPlugin
     */
    private $plugin;

    /**
     * @var AntomLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ToOrderPayment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subjectMock;

    /**
     * @var OrderPayment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderPaymentMock;

    /**
     * @var Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quotePaymentMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(AntomLogger::class);
        $this->subjectMock = $this->createMock(ToOrderPayment::class);
        $this->orderPaymentMock = $this->createMock(OrderPayment::class);
        $this->quotePaymentMock = $this->createMock(Payment::class);

        $this->plugin = new ToOrderPaymentPlugin($this->loggerMock);
    }

    /**
     * Test afterConvert with antom_card payment method and antom_payment_request_id in direct data
     */
    public function testAfterConvertWithAntomCardAndDirectData(): void
    {
        $paymentRequestId = 'test_payment_request_123';

        $this->quotePaymentMock->method('getMethod')->willReturn('antom_card');
        $this->quotePaymentMock->method('getId')->willReturn(1001);

        // Mock getData() to return paymentRequestId for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getData')
            ->willReturnCallback(function ($field = null) use ($paymentRequestId) {
                if ($field === 'antom_payment_request_id') {
                    return $paymentRequestId;
                }
                return null;
            });

        // Mock getAdditionalInformation() to return null for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getAdditionalInformation')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'antom_payment_request_id') {
                    return null;
                }
                return null;
            });

        $this->orderPaymentMock->expects($this->once())
            ->method('setData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);

        $this->orderPaymentMock->method('getData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ->willReturn($paymentRequestId);

        // Use callback to capture all logger calls
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Verify logger calls
        $this->assertGreaterThanOrEqual(1, count($loggerCalls));
        $this->assertEquals('=== ToOrderPayment Plugin: Converting Antom payment ===', $loggerCalls[0]['message']);

        // Find the specific calls we expect
        $foundCopyLog = false;
        $foundDirectLog = false;
        foreach ($loggerCalls as $call) {
            if ($call['message'] === 'Antom payment request ID copied via plugin') {
                $this->assertEquals([
                    'quote_payment_id' => 1001,
                    'antom_payment_request_id' => $paymentRequestId,
                    'order_payment_verification' => $paymentRequestId
                ], $call['context']);
                $foundCopyLog = true;
            }
            if ($call['message'] === 'Found in direct property: antom_payment_request_id') {
                $this->assertEquals(['value' => $paymentRequestId], $call['context']);
                $foundDirectLog = true;
            }
        }
        $this->assertTrue($foundCopyLog, 'Expected copy log not found');
        $this->assertTrue($foundDirectLog, 'Expected direct property log not found');
    }

    /**
     * Test afterConvert with antom_card payment method and antom_payment_request_id in additional information
     */
    public function testAfterConvertWithAntomCardAndAdditionalInfo(): void
    {
        $paymentRequestId = 'test_payment_request_456';

        $this->quotePaymentMock->method('getMethod')->willReturn('antom_card');
        $this->quotePaymentMock->method('getId')->willReturn(1002);

        // Mock getData() to return null for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getData')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'antom_payment_request_id') {
                    return null;
                }
                return null;
            });

        // Mock getAdditionalInformation() to return paymentRequestId for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getAdditionalInformation')
            ->willReturnCallback(function ($field = null) use ($paymentRequestId) {
                if ($field === 'antom_payment_request_id') {
                    return $paymentRequestId;
                }
                return null;
            });

        $this->orderPaymentMock->expects($this->once())
            ->method('setData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);

        $this->orderPaymentMock->method('getData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ->willReturn($paymentRequestId);

        // Use callback to capture all logger calls
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Verify logger calls
        $this->assertGreaterThanOrEqual(1, count($loggerCalls));
        $this->assertEquals('=== ToOrderPayment Plugin: Converting Antom payment ===', $loggerCalls[0]['message']);

        // Find the specific calls we expect
        $foundCopyLog = false;
        $foundAdditionalLog = false;
        foreach ($loggerCalls as $call) {
            if ($call['message'] === 'Antom payment request ID copied via plugin') {
                $this->assertEquals([
                    'quote_payment_id' => 1002,
                    'antom_payment_request_id' => $paymentRequestId,
                    'order_payment_verification' => $paymentRequestId
                ], $call['context']);
                $foundCopyLog = true;
            }
            if ($call['message'] === 'Found in additional_information: antom_payment_request_id') {
                $this->assertEquals(['value' => $paymentRequestId], $call['context']);
                $foundAdditionalLog = true;
            }
        }
        $this->assertTrue($foundCopyLog, 'Expected copy log not found');
        $this->assertTrue($foundAdditionalLog, 'Expected additional info log not found');
    }

    /**
     * Test afterConvert with non-antom_card payment method
     */
    public function testAfterConvertWithNonAntomCardPaymentMethod(): void
    {
        $this->quotePaymentMock->method('getMethod')->willReturn('other_payment_method');

        $this->orderPaymentMock->expects($this->never())
            ->method('setData');

        // Use callback to verify no logger calls for non-antom_card
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Verify no logger calls for non-antom_card payment method
        $this->assertEmpty($loggerCalls, 'No logger calls should be made for non-antom_card payment method');
    }

    /**
     * Test afterConvert with antom_card but no antom_payment_request_id
     */
    public function testAfterConvertWithAntomCardButNoRequestId(): void
    {
        $this->quotePaymentMock->method('getMethod')->willReturn('antom_card');

        // Mock getData() to return null for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getData')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'antom_payment_request_id') {
                    return null;
                }
                return ['some_data' => 'value'];
            });

        // Mock getAdditionalInformation() to return null for 'antom_payment_request_id' field
        $this->quotePaymentMock->method('getAdditionalInformation')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'antom_payment_request_id') {
                    return null;
                }
                return ['other_info' => 'value'];
            });

        $this->orderPaymentMock->expects($this->never())
            ->method('setData');

        // Use callback to capture all logger calls
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $this->loggerMock->method('addAntomWarning')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Verify logger calls
        $this->assertGreaterThanOrEqual(1, count($loggerCalls));
        $this->assertEquals('=== ToOrderPayment Plugin: Converting Antom payment ===', $loggerCalls[0]['message']);

        // Find the warning call
        $foundWarningLog = false;
        foreach ($loggerCalls as $call) {
            if ($call['message'] === 'Antom request ID not found') {
                $this->assertEquals([
                    'quote_payment_data' => ['some_data'],
                    'additional_info_keys' => ['other_info']
                ], $call['context']);
                $foundWarningLog = true;
                break;
            }
        }
        $this->assertTrue($foundWarningLog, 'Expected warning log not found');
    }

    /**
     * Test afterConvert with empty data array parameter
     */
    public function testAfterConvertWithEmptyDataArray(): void
    {
        $paymentRequestId = 'test_payment_request_789';

        $this->quotePaymentMock->method('getMethod')->willReturn('antom_card');
        $this->quotePaymentMock->method('getData')->with('antom_payment_request_id')->willReturn($paymentRequestId);
        $this->quotePaymentMock->method('getAdditionalInformation')->willReturn(null);
        $this->quotePaymentMock->method('getId')->willReturn(1003);

        $this->orderPaymentMock->expects($this->once())
            ->method('setData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);

        $this->orderPaymentMock->method('getData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ->willReturn($paymentRequestId);

        // Use callback to capture all logger calls
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock,
            [] // Empty data array
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Verify logger calls
        $this->assertGreaterThanOrEqual(1, count($loggerCalls));
        $this->assertEquals('=== ToOrderPayment Plugin: Converting Antom payment ===', $loggerCalls[0]['message']);

        // Find the specific calls we expect
        $foundCopyLog = false;
        $foundDirectLog = false;
        foreach ($loggerCalls as $call) {
            if ($call['message'] === 'Antom payment request ID copied via plugin') {
                $this->assertEquals([
                    'quote_payment_id' => 1003,
                    'antom_payment_request_id' => $paymentRequestId,
                    'order_payment_verification' => $paymentRequestId
                ], $call['context']);
                $foundCopyLog = true;
            }
            if ($call['message'] === 'Found in direct property: antom_payment_request_id') {
                $this->assertEquals(['value' => $paymentRequestId], $call['context']);
                $foundDirectLog = true;
            }
        }
        $this->assertTrue($foundCopyLog, 'Expected copy log not found');
        $this->assertTrue($foundDirectLog, 'Expected direct property log not found');
    }

    /**
     * Test afterConvert with data array parameter
     */
    public function testAfterConvertWithDataArray(): void
    {
        $paymentRequestId = 'test_payment_request_999';

        $this->quotePaymentMock->method('getMethod')->willReturn('antom_card');
        $this->quotePaymentMock->method('getData')->with('antom_payment_request_id')->willReturn($paymentRequestId);
        $this->quotePaymentMock->method('getAdditionalInformation')->willReturn(null);
        $this->quotePaymentMock->method('getId')->willReturn(1004);

        $this->orderPaymentMock->expects($this->once())
            ->method('setData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);

        $this->orderPaymentMock->method('getData')
            ->with(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ->willReturn($paymentRequestId);

        // Use callback to capture all logger calls
        $loggerCalls = [];
        $this->loggerMock->method('addAntomInfoLog')
            ->willReturnCallback(function ($message, $context = []) use (&$loggerCalls) {
                $loggerCalls[] = ['message' => $message, 'context' => $context];
                return true;
            });

        $result = $this->plugin->afterConvert(
            $this->subjectMock,
            $this->orderPaymentMock,
            $this->quotePaymentMock,
            ['some_data' => 'value'] // Data array with content
        );

        $this->assertSame($this->orderPaymentMock, $result);

        // Find the specific calls we expect
        $foundCopyLog = false;
        $foundDirectLog = false;
        $convertingLog = false;
        foreach ($loggerCalls as $call) {
            if ($call['message'] === 'Antom payment request ID copied via plugin') {
                $this->assertEquals([
                    'quote_payment_id' => 1004,
                    'antom_payment_request_id' => $paymentRequestId,
                    'order_payment_verification' => $paymentRequestId
                ], $call['context']);
                $foundCopyLog = true;
            }
            if ($call['message'] === 'Found in direct property: antom_payment_request_id') {
                $this->assertEquals(['value' => $paymentRequestId], $call['context']);
                $foundDirectLog = true;
            }
            if ($call['message'] === '=== ToOrderPayment Plugin: Converting Antom payment ===') {
                $this->assertEquals([], $call['context']);
                $convertingLog = true;
            }
        }

        $this->assertTrue($foundCopyLog, 'Expected copy log not found');
        $this->assertTrue($foundDirectLog, 'Expected direct property log not found');
        $this->assertTrue($convertingLog, 'Expected converting log not found');
    }
}
