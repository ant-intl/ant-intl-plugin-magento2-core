<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsProductCodeBuilder;
use Antom\Core\Gateway\AntomConstants;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsProductCodeBuilder
 */
class AmsProductCodeBuilderTest extends TestCase
{
    /**
     * @var AmsProductCodeBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AmsProductCodeBuilder();
    }

    public function testBuildProductCode(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PRODUCT_CODE, $result);
        $this->assertEquals(AntomConstants::CASHIER_PAYMENT, $result[AntomConstants::PRODUCT_CODE]);
    }

    public function testBuildReturnsConstantValue(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result1 = $this->builder->build($buildSubject);
        $result2 = $this->builder->build($buildSubject);

        $this->assertEquals($result1, $result2);
        $this->assertEquals(AntomConstants::CASHIER_PAYMENT, $result1[AntomConstants::PRODUCT_CODE]);
    }

    public function testBuildWithEmptyBuildSubject(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PRODUCT_CODE, $result);
    }

    public function testBuildAlwaysReturnsSameStructure(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        
        for ($i = 0; $i < 5; $i++) {
            $result = $this->builder->build($buildSubject);
            $this->assertEquals([
                AntomConstants::PRODUCT_CODE => AntomConstants::CASHIER_PAYMENT
            ], $result);
        }
    }
}