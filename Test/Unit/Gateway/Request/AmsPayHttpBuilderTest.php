<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\Request\AmsPayHttpBuilder;
use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPayHttpBuilder
 */
class AmsPayHttpBuilderTest extends TestCase
{
    /**
     * @var AmsPayHttpBuilder
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
        $this->builder = new AmsPayHttpBuilder($this->configMock);
        
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
    }

    public function testBuildHttpConfigForStore(): void
    {
        $storeId = 1;
        $expectedGatewayUrl = 'https://test.alipay.com';
        $expectedClientId = 'test_client_id';
        $expectedPrivateKey = 'test_private_key';
        $expectedPublicKey = 'test_public_key';

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->expects($this->once())
            ->method('getAntomGatewayUrl')
            ->with($storeId)
            ->willReturn($expectedGatewayUrl);

        $this->configMock->expects($this->once())
            ->method('getAntomClientId')
            ->with($storeId)
            ->willReturn($expectedClientId);

        $this->configMock->expects($this->once())
            ->method('getMerchantPrivateKey')
            ->with($storeId)
            ->willReturn($expectedPrivateKey);

        $this->configMock->expects($this->once())
            ->method('getAntomPublicKey')
            ->with($storeId)
            ->willReturn($expectedPublicKey);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::METHOD, $result);
        $this->assertEquals(AntomConstants::POST, $result[AntomConstants::METHOD]);

        $this->assertArrayHasKey(AntomConstants::URI, $result);
        $this->assertEquals(AntomConstants::AMS_PAY_URI, $result[AntomConstants::URI]);

        $this->assertArrayHasKey(AntomConstants::CLIENT_CONFIG, $result);
        $expectedClientConfig = [
            AntomConstants::CLIENT_ID => $expectedClientId,
            AntomConstants::MERCHANT_PRIVATE_KEY => $expectedPrivateKey,
            AntomConstants::ANTOM_PUBLIC_KEY => $expectedPublicKey,
            AntomConstants::GATEWAY_URL => $expectedGatewayUrl
        ];
        $this->assertEquals($expectedClientConfig, $result[AntomConstants::CLIENT_CONFIG]);
    }

    public function testBuildWithDifferentStoreId(): void
    {
        $storeId = 2;
        $expectedGatewayUrl = 'https://prod.alipay.com';
        $expectedClientId = 'prod_client_id';
        $expectedPrivateKey = 'prod_private_key';
        $expectedPublicKey = 'prod_public_key';

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomGatewayUrl')->with($storeId)->willReturn($expectedGatewayUrl);
        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn($expectedClientId);
        $this->configMock->method('getMerchantPrivateKey')->with($storeId)->willReturn($expectedPrivateKey);
        $this->configMock->method('getAntomPublicKey')->with($storeId)->willReturn($expectedPublicKey);

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals($expectedGatewayUrl, $result[AntomConstants::CLIENT_CONFIG][AntomConstants::GATEWAY_URL]);
        $this->assertEquals($expectedClientId, $result[AntomConstants::CLIENT_CONFIG][AntomConstants::CLIENT_ID]);
    }

    public function testBuildWithEmptyConfigValues(): void
    {
        $storeId = 1;

        $this->paymentDataObjectMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);

        $this->configMock->method('getAntomGatewayUrl')->with($storeId)->willReturn('');
        $this->configMock->method('getAntomClientId')->with($storeId)->willReturn('');
        $this->configMock->method('getMerchantPrivateKey')->with($storeId)->willReturn('');
        $this->configMock->method('getAntomPublicKey')->with($storeId)->willReturn('');

        $buildSubject = ['payment' => $this->paymentDataObjectMock];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals('', $result[AntomConstants::CLIENT_CONFIG][AntomConstants::GATEWAY_URL]);
        $this->assertEquals('', $result[AntomConstants::CLIENT_CONFIG][AntomConstants::CLIENT_ID]);
    }
}