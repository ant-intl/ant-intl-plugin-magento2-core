<?php

namespace Antom\Core\Test\Unit\Config;

use Antom\Core\Config\AntomConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AntomConfig
 */
class AntomConfigTest extends TestCase
{
    /**
     * @var AntomConfig
     */
    private $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Encryptor|MockObject
     */
    private $encryptorMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->encryptorMock = $this->createMock(Encryptor::class);
        
        $this->config = new AntomConfig(
            $this->scopeConfigMock,
            $this->encryptorMock
        );
    }

    public function testIsDebugReturnsTrueWhenEnabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/debug', 'store', 1)
            ->willReturn(true);

        $this->assertTrue($this->config->isDebug(1));
    }

    public function testIsDebugReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/debug', 'default', null)
            ->willReturn(false);

        $this->assertFalse($this->config->isDebug());
    }

    public function testIsLiveEnvReturnsTrueWhenLive(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);

        $this->assertTrue($this->config->isLiveEnv(1));
    }

    public function testIsLiveEnvReturnsFalseWhenSandbox(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'default', null)
            ->willReturn(false);

        $this->assertFalse($this->config->isLiveEnv());
    }

    public function testGetAntomGatewayUrlReturnsLiveUrl(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_gateway_url', 'store', 1)
            ->willReturn('https://live.alipay.com');

        $this->assertEquals('https://live.alipay.com', $this->config->getAntomGatewayUrl(1));
    }

    public function testGetAntomGatewayUrlReturnsSandboxUrl(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_sandbox_gateway_url', 'store', 1)
            ->willReturn('https://sandbox.alipay.com');

        $this->assertEquals('https://sandbox.alipay.com', $this->config->getAntomGatewayUrl(1));
    }

    public function testGetAntomGatewayUrlReturnsEmptyWhenNotSet(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_gateway_url', 'store', 1)
            ->willReturn(null);

        $this->assertEquals('', $this->config->getAntomGatewayUrl(1));
    }

    public function testGetAntomClientIdReturnsLiveClientId(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_client_id', 'store', 1)
            ->willReturn('live_client_123');

        $this->assertEquals('live_client_123', $this->config->getAntomClientId(1));
    }

    public function testGetAntomClientIdReturnsSandboxClientId(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_sandbox_client_id', 'store', 1)
            ->willReturn('sandbox_client_456');

        $this->assertEquals('sandbox_client_456', $this->config->getAntomClientId(1));
    }

    public function testGetMerchantPrivateKeyReturnsLiveKey(): void
    {
        $encryptedKey = 'encrypted_live_key';
        $decryptedKey = 'live_private_key_content';

        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/merchant_private_key', 'store', 1)
            ->willReturn($encryptedKey);
        $this->encryptorMock->method('decrypt')
            ->with($encryptedKey)
            ->willReturn($decryptedKey);

        $this->assertEquals($decryptedKey, $this->config->getMerchantPrivateKey(1));
    }

    public function testGetMerchantPrivateKeyReturnsSandboxKey(): void
    {
        $encryptedKey = 'encrypted_sandbox_key';
        $decryptedKey = 'sandbox_private_key_content';

        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/merchant_sandbox_private_key', 'store', 1)
            ->willReturn($encryptedKey);
        $this->encryptorMock->method('decrypt')
            ->with($encryptedKey)
            ->willReturn($decryptedKey);

        $this->assertEquals($decryptedKey, $this->config->getMerchantPrivateKey(1));
    }

    public function testGetMerchantPrivateKeyWithLiveModeOverride(): void
    {
        $encryptedKey = 'encrypted_live_key';
        $decryptedKey = 'live_private_key_content';

        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/merchant_private_key', 'store', 1)
            ->willReturn($encryptedKey);
        $this->encryptorMock->method('decrypt')
            ->with($encryptedKey)
            ->willReturn($decryptedKey);

        $this->assertEquals($decryptedKey, $this->config->getMerchantPrivateKey(1, true));
    }

    public function testGetAntomPublicKeyReturnsLiveKey(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_public_key', 'store', 1)
            ->willReturn('live_public_key_content');

        $this->assertEquals('live_public_key_content', $this->config->getAntomPublicKey(1));
    }

    public function testGetAntomPublicKeyReturnsSandboxKey(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/environment', 'store', 1)
            ->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/antom_sandbox_public_key', 'store', 1)
            ->willReturn('sandbox_public_key_content');

        $this->assertEquals('sandbox_public_key_content', $this->config->getAntomPublicKey(1));
    }

    public function testGetEnabledCardsReturnsEmptyWhenDisabled(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('payment/antom_card/active', 'store', 1)
            ->willReturn(false);

        $this->assertEquals([], $this->config->getEnabledCards(1));
    }

    public function testGetEnabledCardsReturnsEnabledCards(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnMap([
                ['payment/antom_card/active', 'store', 1, true],
                ['payment/antom_card_visa/active', 'store', 1, true],
                ['payment/antom_card_mastercard/active', 'store', 1, false],
                ['payment/antom_card_amex/active', 'store', 1, true]
            ]);

        $result = $this->config->getEnabledCards(1);
        $this->assertContains('antom_card_visa', $result);
        $this->assertContains('antom_card_amex', $result);
        $this->assertNotContains('antom_card_mastercard', $result);
    }

    public function testGetConfigWithDefaultScope(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/test_field', 'default', null)
            ->willReturn('test_value');

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->config, 'test_field', null, false);
        $this->assertEquals('test_value', $result);
    }

    public function testGetConfigWithStoreScope(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('antom/general/test_field', 'store', 1)
            ->willReturn('store_value');

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->config, 'test_field', 1);
        $this->assertEquals('store_value', $result);
    }

    public function testGetConfigWithFlag(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('antom/general/test_flag', 'store', 1)
            ->willReturn(true);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->config, 'test_flag', 1, true);
        $this->assertTrue($result);
    }

    public function testGetConfigWithCustomPattern(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('custom/pattern/test_field', 'store', 1)
            ->willReturn('custom_value');

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->config, 'test_field', 1, false, 'custom/pattern/%s');
        $this->assertEquals('custom_value', $result);
    }
}