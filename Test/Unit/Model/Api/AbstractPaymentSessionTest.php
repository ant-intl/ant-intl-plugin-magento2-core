<?php

namespace Antom\Core\Test\Unit\Model\Api;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Helper\RequestHelper;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Api\AbstractPaymentSession;
use Antom\Core\Model\Api\GuestAntomCreatePaymentSession;
use Antom\Core\Model\Request\AlipayCreatePaymentSessionRequest;
use Client\DefaultAlipayClient;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for GuestAntomCreatePaymentSession class
 */
class AbstractPaymentSessionTest extends TestCase
{
    /**
     * @var GuestAntomCreatePaymentSession
     */
    private $model;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepositoryMock;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteIdMock;

    /**
     * @var RequestHelper|MockObject
     */
    private $requestHelperMock;

    /**
     * @var AntomConfig|MockObject
     */
    private $antomConfigMock;

    /**
     * @var AntomLogger|MockObject
     */
    private $antomLoggerMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var StoreManagerInterface|(StoreManagerInterface&object&MockObject)|(StoreManagerInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $storeManagerMock;

    /**
     * @var UserContextInterface|(UserContextInterface&object&MockObject)|(UserContextInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $userContextMock;

    /**
     * @var CurrencyInterface|(CurrencyInterface&object&MockObject)|(CurrencyInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $currencyMock;

    /**
     * @var StoreInterface|(StoreInterface&object&MockObject)|(StoreInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $storeMock;

    protected function setUp(): void
    {

        $this->model = $this->getMockBuilder(GuestAntomCreatePaymentSession::class)
            ->onlyMethods(['createAlipayClient'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->requestHelperMock = $this->createMock(RequestHelper::class);
        $this->antomConfigMock = $this->createMock(AntomConfig::class);
        $this->antomLoggerMock = $this->createMock(AntomLogger::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->maskedQuoteIdToQuoteIdMock = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);

        $this->currencyMock = $this->createMock(CurrencyInterface::class);
        $this->storeMock = $this->createMock(Store::class);

        $this->storeMock->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $this->model = new GuestAntomCreatePaymentSession(
            $this->cartRepositoryMock,
            $this->requestHelperMock,
            $this->storeManagerMock,
            $this->maskedQuoteIdToQuoteIdMock,
            $this->antomConfigMock,
            $this->userContextMock,
            $this->antomLoggerMock
        );
    }

    /**
     * Test createPaymentSession with valid guest cart
     */
    public function testCreatePaymentSessionWithValidGuestCart(): void
    {
        $cartId = 'masked_cart_123';
        $email = 'test@example.com';
        $storeId = 1;
        $realQuoteId = 789;
        $domain = 'https://example.com';
        $gatewayUrl = 'https://gateway.antom.com';
        $merchantPrivateKey = 'test_private_key';
        $antomPublicKey = 'test_public_key';
        $clientId = 'test_client_id';

        $enabledCards = [
            'antom_card_visa',
            'antom_card_mastercard',
            'antom_card_amex',
        ];

        $this->userContextMock
            ->expects($this->once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_GUEST);

        $this->maskedQuoteIdToQuoteIdMock
            ->method('execute')
            ->with($cartId)
            ->willReturn($realQuoteId);

        $this->quoteMock->method('getItemsCount')
            ->willReturn(1);

        $this->quoteMock->method('getCurrency')
            ->willReturn($this->currencyMock);
        $this->currencyMock->method('getQuoteCurrencyCode')
            ->willReturn("USD");

        $this->quoteMock->method('getStoreId')
            ->willReturn($storeId);

        $this->storeManagerMock->method('getStore')
            ->with($storeId)
            ->willReturn($this->storeMock);

        $this->storeMock->method('getBaseUrl')
            ->willReturn($domain);

        // Mock cart repository
        $this->cartRepositoryMock->method('get')
            ->with($realQuoteId)
            ->willReturn($this->quoteMock);

        // Mock config
        $this->antomConfigMock->method('getAntomGatewayUrl')
            ->with($storeId)
            ->willReturn($gatewayUrl);
        $this->antomConfigMock->method('getMerchantPrivateKey')
            ->with($storeId)
            ->willReturn($merchantPrivateKey);
        $this->antomConfigMock->method('getEnabledCards')
            ->willReturn($enabledCards);
        $this->antomConfigMock->method('getAntomPublicKey')
            ->with($storeId)
            ->willReturn($antomPublicKey);
        $this->antomConfigMock->method('getAntomClientId')
            ->with($storeId)
            ->willReturn($clientId);
        
        $alipayClientMock = $this->createMock(DefaultAlipayClient::class);

        $mockAlipayResponseData = [
            'paymentSessionData' => 'test_session_data',
            'paymentSessionExpiryTime' => '2025-12-09T10:26:51+08:00',
            'paymentSessionId' => 'test_session_id',
            'result' => [
                'resultCode' => 'SUCCESS',
                'resultMessage' => 'success.',
                'resultStatus' => 'S'
            ],
            'paymentRequestId' => 'DFCE3759F_20251205004208829186'
        ];

        // 将数组转换为 stdClass 对象（模拟 json_decode 的结果）
        $alipayResponseMock = json_decode(json_encode($mockAlipayResponseData));

        $this->model = $this->getMockBuilder(AbstractPaymentSession::class)
            ->setConstructorArgs([
                $this->cartRepositoryMock,
                $this->requestHelperMock,
                $this->storeManagerMock,
                $this->maskedQuoteIdToQuoteIdMock,
                $this->antomConfigMock,
                $this->userContextMock,
                $this->antomLoggerMock
            ])
            ->onlyMethods(['createAlipayClient'])
            ->getMock();

        // 在测试中
        $this->model->expects($this->once())
            ->method('createAlipayClient')
            ->with($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId)
            ->willReturn($alipayClientMock);

        // Mock request helper
        $this->requestHelperMock->method('getDomain')
            ->willReturn($domain);

        $alipayClientMock->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(AlipayCreatePaymentSessionRequest::class))
            ->willReturn($alipayResponseMock);

        $actualResponse = $this->model->createPaymentSessionHelper($cartId, $email);

        $responseData = json_decode($actualResponse, true);

        $this->assertEquals('SUCCESS', $responseData['result']['resultCode']);
        $this->assertEquals('S', $responseData['result']['resultStatus']);

        // 验证数据类型
        $this->assertMatchesRegularExpression(
            '/^D[0-9A-F]{8}_\d{20}$/',
            $responseData['paymentRequestId'],
            'String should start with D, followed by 8 hex chars, underscore, and 20 digits'
        );
        $this->assertEquals("test_session_data", $responseData['paymentSessionData']);
        $this->assertEquals("test_session_id", $responseData['paymentSessionId']);
        $this->assertIsArray($responseData['result']);
    }
    
    /**
     * Test createPaymentSession with invalid cart ID
     */
    public function testCreatePaymentSessionWithInvalidCartId(): void
    {
        $cartId = 'invalid_cart';
        $email = 'test@example.com';

        $this->userContextMock
            ->expects($this->once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_GUEST);

        $this->cartRepositoryMock->method('get')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException());
        $this->expectException(NoSuchEntityException::class);

        $this->model->createPaymentSession($cartId, $email);
    }

    /**
     * Test createPaymentSession with missing quote
     */
    public function testCreatePaymentSessionWithMissingQuote(): void
    {
        $cartId = 'masked_cart_456';
        $email = 'test@example.com';
        $this->userContextMock
            ->expects($this->once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_GUEST);
        $this->expectException(NoSuchEntityException::class);
        $this->model->createPaymentSession($cartId, $email);
    }

    public function testGetQuoteByCartIdWithCustomerUserTypeSuccess(): void
    {
        $cartId = '123';
        $customerId = 456;
        $quoteId = 123;
        $this->userContextMock
            ->expects($this->once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->userContextMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn($customerId);
        $this->cartRepositoryMock
            ->expects($this->once())
            ->method('getActiveForCustomer')
            ->with($customerId)
            ->willReturn($this->quoteMock);
        $this->quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getQuoteByCartId');

        $method->setAccessible(true);

        $result = $method->invoke($this->model, $cartId, true);
        $this->assertSame($this->quoteMock, $result);
    }
}
