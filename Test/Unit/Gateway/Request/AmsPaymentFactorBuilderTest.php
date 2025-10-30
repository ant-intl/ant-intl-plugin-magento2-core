<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsPaymentFactorBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\PaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPaymentFactorBuilder
 */
class AmsPaymentFactorBuilderTest extends TestCase
{
    /**
     * @var AmsPaymentFactorBuilder
     */
    private $builder;

    /**
     * @var RequestHelper|MockObject
     */
    private $requestHelperMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->requestHelperMock = $this->createMock(RequestHelper::class);
        $this->builder = new AmsPaymentFactorBuilder($this->requestHelperMock);
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);
    }

    public function testBuildForCardPayment(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_card');

        $this->requestHelperMock->expects($this->once())
            ->method('isAlipayCnPaymentMethod')
            ->with('antom_card')
            ->willReturn(false);

        $this->requestHelperMock->expects($this->once())
            ->method('isCardPaymentMethod')
            ->with('antom_card')
            ->willReturn(true);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_FACTOR, $result);
        $this->assertEquals([
            AntomConstants::IS_AUTHORIZATION => true
        ], $result[AntomConstants::PAYMENT_FACTOR]);
    }

    public function testBuildForAlipayCnPayment(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_alipay_cn');

        $this->requestHelperMock->expects($this->once())
            ->method('isAlipayCnPaymentMethod')
            ->with('antom_alipay_cn')
            ->willReturn(true);

        $this->requestHelperMock->expects($this->never())
            ->method('isCardPaymentMethod');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals([], $result);
    }

    public function testBuildForOtherPaymentMethod(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_other');

        $this->requestHelperMock->expects($this->once())
            ->method('isAlipayCnPaymentMethod')
            ->with('antom_other')
            ->willReturn(false);

        $this->requestHelperMock->expects($this->once())
            ->method('isCardPaymentMethod')
            ->with('antom_other')
            ->willReturn(false);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_FACTOR, $result);
        $this->assertEquals([
            AntomConstants::IS_AUTHORIZATION => false
        ], $result[AntomConstants::PAYMENT_FACTOR]);
    }

    public function testBuildWithEmptyPaymentMethod(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('');

        $this->requestHelperMock->expects($this->once())
            ->method('isAlipayCnPaymentMethod')
            ->with('')
            ->willReturn(false);

        $this->requestHelperMock->expects($this->once())
            ->method('isCardPaymentMethod')
            ->with('')
            ->willReturn(false);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_FACTOR, $result);
        $this->assertEquals([
            AntomConstants::IS_AUTHORIZATION => false
        ], $result[AntomConstants::PAYMENT_FACTOR]);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_card');

        $this->requestHelperMock->method('isAlipayCnPaymentMethod')->willReturn(false);
        $this->requestHelperMock->method('isCardPaymentMethod')->willReturn(true);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PAYMENT_FACTOR, $result);
    }
}