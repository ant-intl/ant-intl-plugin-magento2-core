<?php

namespace Antom\Core\Test\Unit\Plugin;

use Antom\Core\Plugin\PaymentMethodIsActivePlugin;
use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Model\Method\Adapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodIsActivePluginTest extends TestCase
{
    /**
     * @var PaymentMethodIsActivePlugin
     */
    private $plugin;

    /**
     * @var AntomConfig|MockObject
     */
    private $antomConfigMock;

    /**
     * @var Adapter|MockObject
     */
    private $adapterMock;

    protected function setUp(): void
    {
        $this->antomConfigMock = $this->createMock(AntomConfig::class);
        $this->adapterMock = $this->createMock(Adapter::class);
        
        $this->plugin = new PaymentMethodIsActivePlugin(
            $this->antomConfigMock
        );
    }

    /**
     * Test afterIsActive with Antom Card payment method and enabled cards
     */
    public function testAfterIsActiveWithAntomCardAndEnabledCards(): void
    {
        $this->adapterMock->method('getCode')->willReturn(AntomConstants::MAGENTO_ANTOM_CARD);
        $this->adapterMock->method('getStore')->willReturn(1);
        
        $this->antomConfigMock->method('getEnabledCards')
            ->with(1)
            ->willReturn(['visa', 'mastercard']);

        $result = $this->plugin->afterIsActive($this->adapterMock, true);

        $this->assertTrue($result);
    }

    /**
     * Test afterIsActive with Antom Card payment method and no enabled cards
     */
    public function testAfterIsActiveWithAntomCardAndNoEnabledCards(): void
    {
        $this->adapterMock->method('getCode')->willReturn(AntomConstants::MAGENTO_ANTOM_CARD);
        $this->adapterMock->method('getStore')->willReturn(1);
        
        $this->antomConfigMock->method('getEnabledCards')
            ->with(1)
            ->willReturn([]);

        $result = $this->plugin->afterIsActive($this->adapterMock, true);

        $this->assertFalse($result);
    }

    /**
     * Test afterIsActive with Antom Card payment method and null enabled cards
     */
    public function testAfterIsActiveWithAntomCardAndNullEnabledCards(): void
    {
        $this->adapterMock->method('getCode')->willReturn(AntomConstants::MAGENTO_ANTOM_CARD);
        $this->adapterMock->method('getStore')->willReturn(1);
        
        $this->antomConfigMock->method('getEnabledCards')
            ->with(1)
            ->willReturn(null);

        $result = $this->plugin->afterIsActive($this->adapterMock, true);

        $this->assertFalse($result);
    }

    /**
     * Test afterIsActive with non-Antom Card payment method
     */
    public function testAfterIsActiveWithNonAntomCardPaymentMethod(): void
    {
        $this->adapterMock->method('getCode')->willReturn('other_payment_method');

        $result = $this->plugin->afterIsActive($this->adapterMock, true);

        $this->assertTrue($result);
    }

    /**
     * Test afterIsActive with Antom Card payment method but original result is false
     */
    public function testAfterIsActiveWithAntomCardAndOriginalResultFalse(): void
    {
        $this->adapterMock->method('getCode')->willReturn(AntomConstants::MAGENTO_ANTOM_CARD);

        $result = $this->plugin->afterIsActive($this->adapterMock, false);

        $this->assertFalse($result);
    }

    /**
     * Test afterIsActive with different store IDs
     */
    public function testAfterIsActiveWithDifferentStoreIds(): void
    {
        $this->adapterMock->method('getCode')->willReturn(AntomConstants::MAGENTO_ANTOM_CARD);
        $this->adapterMock->method('getStore')->willReturn(2);
        
        $this->antomConfigMock->method('getEnabledCards')
            ->with(2)
            ->willReturn(['amex']);

        $result = $this->plugin->afterIsActive($this->adapterMock, true);

        $this->assertTrue($result);
    }
}