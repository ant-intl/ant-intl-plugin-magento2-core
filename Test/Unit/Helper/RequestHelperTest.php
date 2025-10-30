<?php

namespace Antom\Core\Test\Unit\Helper;

use Antom\Core\Helper\RequestHelper;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestHelperTest extends TestCase
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var AntomLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var RequestHttp|MockObject
     */
    private $requestMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->loggerMock = $this->createMock(AntomLogger::class);
        $this->requestMock = $this->createMock(RequestHttp::class);
        $this->storeMock = $this->createMock(Store::class);

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->requestHelper = new RequestHelper(
            $this->contextMock,
            $this->storeManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test composeEnvInfo method with web browser
     */
    public function testComposeEnvInfoWithWebBrowser(): void
    {
        $this->requestMock->method('getServer')
            ->willReturnMap([
                ['HTTP_USER_AGENT', null, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'],
                ['HTTP_ACCEPT', null, 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                ['REMOTE_ADDR', null, '192.168.1.100'],
                ['HTTP_ACCEPT_LANGUAGE', null, 'en-US,en;q=0.5']
            ]);

        $result = $this->requestHelper->composeEnvInfo();

        $expected = [
            AntomConstants::CLIENT_IP => '192.168.1.100',
            AntomConstants::TERMINAL_TYPE => 'WEB',
            AntomConstants::BROWSER_INFO => [
                AntomConstants::LANGUAGE => 'en-US,en;q=0.5',
                'acceptHeader' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test composeEnvInfo method with Android browser
     */
    public function testComposeEnvInfoWithAndroidBrowser(): void
    {
        $this->requestMock->method('getServer')
            ->willReturnMap([
                ['HTTP_USER_AGENT', null, 'Mozilla/5.0 (android 10; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0'],
                ['HTTP_ACCEPT', null, 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                ['REMOTE_ADDR', null, '192.168.1.100'],
                ['HTTP_ACCEPT_LANGUAGE', null, 'en-US,en;q=0.5']
            ]);

        $result = $this->requestHelper->composeEnvInfo();

        $expected = [
            AntomConstants::CLIENT_IP => '192.168.1.100',
            AntomConstants::OS_TYPE => 'ANDROID',
            AntomConstants::TERMINAL_TYPE => 'WAP',
            AntomConstants::BROWSER_INFO => [
                AntomConstants::LANGUAGE => 'en-US,en;q=0.5',
                'acceptHeader' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test composeEnvInfo method with empty server variables
     */
    public function testComposeEnvInfoWithEmptyServerVariables(): void
    {
        $this->requestMock->method('getServer')
            ->willReturnMap([
                ['HTTP_USER_AGENT', null, null],
                ['HTTP_ACCEPT', null, null],
                ['REMOTE_ADDR', null, null],
                ['HTTP_ACCEPT_LANGUAGE', null, null]
            ]);

        $result = $this->requestHelper->composeEnvInfo();

        $expected = [
            AntomConstants::TERMINAL_TYPE => 'WEB',
            AntomConstants::BROWSER_INFO => []
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getDomain method with store manager success
     */
    public function testGetDomainWithStoreManagerSuccess(): void
    {
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->method('getBaseUrl')->willReturn('https://example.com');

        $result = $this->requestHelper->getDomain();

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test getDomain method with store manager exception
     */
    public function testGetDomainWithStoreManagerException(): void
    {
        $this->storeManagerMock->method('getStore')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());
        
        $this->loggerMock->expects($this->once())
            ->method('addAntomWarning')
            ->with($this->stringContains('can not find domain from [storeManager]'));

        $this->requestMock->method('isSecure')->willReturn(true);
        $this->requestMock->method('getServer')->with('HTTP_HOST')->willReturn('example.com');

        $result = $this->requestHelper->getDomain();

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test getDomain method with empty base URL
     */
    public function testGetDomainWithEmptyBaseUrl(): void
    {
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->method('getBaseUrl')->willReturn('');

        $this->requestMock->method('isSecure')->willReturn(false);
        $this->requestMock->method('getServer')->with('HTTP_HOST')->willReturn('example.com');

        $result = $this->requestHelper->getDomain();

        $this->assertEquals('http://example.com', $result);
    }

    /**
     * Test isAlipayCnPaymentMethod method with Alipay CN
     */
    public function testIsAlipayCnPaymentMethodWithAlipayCn(): void
    {
        $result = $this->requestHelper->isAlipayCnPaymentMethod(AntomConstants::MAGENTO_ALIPAY_CN);
        $this->assertTrue($result);
    }

    /**
     * Test isAlipayCnPaymentMethod method with other payment method
     */
    public function testIsAlipayCnPaymentMethodWithOtherPaymentMethod(): void
    {
        $result = $this->requestHelper->isAlipayCnPaymentMethod('other_payment_method');
        $this->assertFalse($result);
    }

    /**
     * Test isCardPaymentMethod method with card payment
     */
    public function testIsCardPaymentMethodWithCardPayment(): void
    {
        $result = $this->requestHelper->isCardPaymentMethod(AntomConstants::MAGENTO_ANTOM_CARD);
        $this->assertTrue($result);
    }

    /**
     * Test isCardPaymentMethod method with other payment method
     */
    public function testIsCardPaymentMethodWithOtherPaymentMethod(): void
    {
        $result = $this->requestHelper->isCardPaymentMethod('other_payment_method');
        $this->assertFalse($result);
    }

    /**
     * Test getOrderAmount method with USD currency
     */
    public function testGetOrderAmountWithUsdCurrency(): void
    {
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $orderMock->method('getCurrencyCode')->willReturn('USD');
        $orderMock->method('getGrandTotalAmount')->willReturn(99.99);

        $result = $this->requestHelper->getOrderAmount($orderMock);

        $expected = [
            AntomConstants::CURRENCY => 'USD',
            AntomConstants::VALUE => 9999
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getOrderAmount method with JPY currency
     */
    public function testGetOrderAmountWithJpyCurrency(): void
    {
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $orderMock->method('getCurrencyCode')->willReturn('JPY');
        $orderMock->method('getGrandTotalAmount')->willReturn(1000);

        $result = $this->requestHelper->getOrderAmount($orderMock);

        $expected = [
            AntomConstants::CURRENCY => 'JPY',
            AntomConstants::VALUE => 1000
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getOrderAmount method with KWD currency
     */
    public function testGetOrderAmountWithKwdCurrency(): void
    {
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $orderMock->method('getCurrencyCode')->willReturn('KWD');
        $orderMock->method('getGrandTotalAmount')->willReturn(10.123);

        $result = $this->requestHelper->getOrderAmount($orderMock);

        $expected = [
            AntomConstants::CURRENCY => 'KWD',
            AntomConstants::VALUE => 10123
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getOrderAmount method with unsupported currency
     */
    public function testGetOrderAmountWithUnsupportedCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency is not supported for this order.');

        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $orderMock->method('getCurrencyCode')->willReturn('XYZ');
        $orderMock->method('getGrandTotalAmount')->willReturn(100);

        $this->requestHelper->getOrderAmount($orderMock);
    }

    /**
     * Test amountConvert method with USD currency
     */
    public function testAmountConvertWithUsdCurrency(): void
    {
        $amount = [
            AntomConstants::CURRENCY => 'USD',
            AntomConstants::VALUE => 9999
        ];

        $result = $this->requestHelper->amountConvert($amount);

        $expected = [
            AntomConstants::CURRENCY => 'USD',
            AntomConstants::AMOUNT => '99.99'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test amountConvert method with JPY currency
     */
    public function testAmountConvertWithJpyCurrency(): void
    {
        $amount = [
            AntomConstants::CURRENCY => 'JPY',
            AntomConstants::VALUE => 1000
        ];

        $result = $this->requestHelper->amountConvert($amount);

        $expected = [
            AntomConstants::CURRENCY => 'JPY',
            AntomConstants::AMOUNT => '1000'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test amountConvert method with KWD currency
     */
    public function testAmountConvertWithKwdCurrency(): void
    {
        $amount = [
            AntomConstants::CURRENCY => 'KWD',
            AntomConstants::VALUE => 10123
        ];

        $result = $this->requestHelper->amountConvert($amount);

        $expected = [
            AntomConstants::CURRENCY => 'KWD',
            AntomConstants::AMOUNT => '10.123'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test amountConvert method with unsupported currency
     */
    public function testAmountConvertWithUnsupportedCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency is not supported for this order.');

        $amount = [
            AntomConstants::CURRENCY => 'XYZ',
            AntomConstants::VALUE => 100
        ];

        $this->requestHelper->amountConvert($amount);
    }
}