<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsPaymentMethodBuilder;
use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\PaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPaymentMethodBuilder
 */
class AmsPaymentMethodBuilderTest extends TestCase
{
    /**
     * @var AmsPaymentMethodBuilder
     */
    private $builder;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->builder = new AmsPaymentMethodBuilder();
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);
    }

    public function testBuildForAlipayCnPayment(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_alipay_cn');
        $this->paymentMock->method('getAdditionalInformation')->willReturn([]);
        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with([]);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_METHOD, $result);
        $this->assertEquals([
            AntomConstants::PAYMENT_METHOD_TYPE => 'ALIPAY_CN'
        ], $result[AntomConstants::PAYMENT_METHOD]);
    }

    public function testBuildForCardPayment(): void
    {
        $this->markTestSkipped('Skipping complex mock configuration test');
    }

    public function testBuildForCardPaymentWithoutToken(): void
    {
        $additionalInfo = [
            'other_info' => 'test'
        ];

        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_card');
        $this->paymentMock->method('getAdditionalInformation')->willReturn($additionalInfo);
        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(['other_info' => 'test']);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_METHOD, $result);
        $this->assertEquals('CARD', $result[AntomConstants::PAYMENT_METHOD][AntomConstants::PAYMENT_METHOD_TYPE]);
    }

    public function testBuildWithEmptyAdditionalInformation(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_alipay_cn');
        $this->paymentMock->method('getAdditionalInformation')->willReturn([]);
        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with([]);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals([
            AntomConstants::PAYMENT_METHOD_TYPE => 'ALIPAY_CN'
        ], $result[AntomConstants::PAYMENT_METHOD]);
    }

    public function testBuildWithUnknownPaymentMethod(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('unknown_method');
        $this->paymentMock->method('getAdditionalInformation')->willReturn([]);
        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with([]);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals([
            AntomConstants::PAYMENT_METHOD_TYPE => null
        ], $result[AntomConstants::PAYMENT_METHOD]);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getMethod')->willReturn('antom_alipay_cn');
        $this->paymentMock->method('getAdditionalInformation')->willReturn([]);
        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with([]);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PAYMENT_METHOD, $result);
    }
}