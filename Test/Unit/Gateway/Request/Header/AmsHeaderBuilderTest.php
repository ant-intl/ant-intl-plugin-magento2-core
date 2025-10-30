<?php

namespace Antom\Core\Test\Unit\Gateway\Request\Header;

use Antom\Core\Gateway\Request\Header\AmsHeaderBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Config\AntomConfig;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsHeaderBuilder
 */
class AmsHeaderBuilderTest extends TestCase
{
    /**
     * @var AmsHeaderBuilder
     */
    private $builder;

    /**
     * @var AntomConfig|MockObject
     */
    private $configMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->configMock = $this->createMock(AntomConfig::class);
        $this->builder = new AmsHeaderBuilder($this->configMock);
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
    }

    public function testBuildHeadersForStore(): void
    {
        $storeId = 1;
        $expectedClientId = 'test_client_id';

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->expects($this->once())
            ->method('getAntomClientId')
            ->with($storeId)
            ->willReturn($expectedClientId);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::HEADERS, $result);
        $this->assertArrayHasKey(AntomConstants::CLIENT_ID, $result[AntomConstants::HEADERS]);
        $this->assertArrayHasKey(AntomConstants::CONTENT_TYPE, $result[AntomConstants::HEADERS]);
        $this->assertArrayHasKey(AntomConstants::REQUEST_TIME, $result[AntomConstants::HEADERS]);

        $this->assertEquals($expectedClientId, $result[AntomConstants::HEADERS][AntomConstants::CLIENT_ID]);
        $this->assertEquals('application/json; charset=UTF-8', $result[AntomConstants::HEADERS][AntomConstants::CONTENT_TYPE]);
        $this->assertIsString($result[AntomConstants::HEADERS][AntomConstants::REQUEST_TIME]);
    }

    public function testBuildHeadersWithDifferentStoreId(): void
    {
        $storeId = 2;
        $expectedClientId = 'prod_client_id';

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn($expectedClientId);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals($expectedClientId, $result[AntomConstants::HEADERS][AntomConstants::CLIENT_ID]);
    }

    public function testBuildHeadersWithEmptyClientId(): void
    {
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn('');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('', $result[AntomConstants::HEADERS][AntomConstants::CLIENT_ID]);
    }

    public function testBuildHeadersRequestTimeIsNumericString(): void
    {
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn('test_client');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $requestTime = $result[AntomConstants::HEADERS][AntomConstants::REQUEST_TIME];
        $this->assertIsString($requestTime);
        $this->assertIsNumeric($requestTime);
        $this->assertGreaterThan(0, (int)$requestTime);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn('test_client');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::HEADERS, $result);
        $this->assertIsArray($result[AntomConstants::HEADERS]);
        $this->assertCount(3, $result[AntomConstants::HEADERS]);
    }

    public function testBuildHeadersContentTypeIsConstant(): void
    {
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn('test_client');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('application/json; charset=UTF-8', $result[AntomConstants::HEADERS][AntomConstants::CONTENT_TYPE]);
    }
}