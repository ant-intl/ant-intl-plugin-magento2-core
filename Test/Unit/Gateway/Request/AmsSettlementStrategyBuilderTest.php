<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsSettlementStrategyBuilder;
use Antom\Core\Gateway\AntomConstants;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsSettlementStrategyBuilder
 */
class AmsSettlementStrategyBuilderTest extends TestCase
{
    /**
     * @var AmsSettlementStrategyBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AmsSettlementStrategyBuilder();
    }

    public function testBuildSettlementStrategy(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::SETTLEMENT_STRATEGY, $result);
        $this->assertEquals([
            AntomConstants::SETTLEMENT_CURRENCY => 'USD'
        ], $result[AntomConstants::SETTLEMENT_STRATEGY]);
    }

    public function testBuildReturnsConstantValue(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result1 = $this->builder->build($buildSubject);
        $result2 = $this->builder->build($buildSubject);

        $this->assertEquals($result1, $result2);
        $this->assertEquals('USD', $result1[AntomConstants::SETTLEMENT_STRATEGY][AntomConstants::SETTLEMENT_CURRENCY]);
    }

    public function testBuildWithEmptyBuildSubject(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::SETTLEMENT_STRATEGY, $result);
    }

    public function testBuildAlwaysReturnsSameStructure(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        
        for ($i = 0; $i < 5; $i++) {
            $result = $this->builder->build($buildSubject);
            $this->assertEquals([
                AntomConstants::SETTLEMENT_STRATEGY => [
                    AntomConstants::SETTLEMENT_CURRENCY => 'USD'
                ]
            ], $result);
        }
    }

    public function testBuildSettlementStrategyStructure(): void
    {
        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result[AntomConstants::SETTLEMENT_STRATEGY]);
        $this->assertCount(1, $result[AntomConstants::SETTLEMENT_STRATEGY]);
        $this->assertArrayHasKey(AntomConstants::SETTLEMENT_CURRENCY, $result[AntomConstants::SETTLEMENT_STRATEGY]);
    }
}