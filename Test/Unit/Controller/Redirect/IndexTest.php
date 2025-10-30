<?php
declare(strict_types=1);

namespace Antom\Core\Test\Unit\Controller\Redirect;

use Antom\Core\Controller\Redirect\Index;
use Antom\Core\Config\AntomConfig;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Helper\PaymentStatusHelper;
use Antom\Core\Gateway\AntomConstants;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteRepository;
use PHPUnit\Framework\MockObject\MockObject;

class IndexTest extends TestCase
{
    private Index $controller;

    /** @var MockObject */
    private $orderFactoryMock;

    /** @var MockObject */
    private $storeManagerMock;

    /** @var MockObject */
    private $configMock;

    /** @var MockObject */
    private $loggerMock;

    /** @var MockObject */
    private $sessionMock;

    /** @var MockObject */
    private $cartRepositoryMock;

    /** @var MockObject */
    private $resultFactoryMock;

    /** @var MockObject */
    private $requestMock;

    /** @var MockObject */
    private $paymentStatusHelperMock;

    /** @var MockObject */
    private $quoteRepositoryMock;

    protected function setUp(): void
    {
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->configMock = $this->createMock(AntomConfig::class);
        $this->loggerMock = $this->createMock(AntomLogger::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->paymentStatusHelperMock = $this->createMock(PaymentStatusHelper::class);
        $this->quoteRepositoryMock = $this->createMock(QuoteRepository::class);

        $this->controller = new Index(
            $this->orderFactoryMock,
            $this->storeManagerMock,
            $this->configMock,
            $this->loggerMock,
            $this->sessionMock,
            $this->cartRepositoryMock,
            $this->resultFactoryMock,
            $this->requestMock,
            $this->paymentStatusHelperMock,
            $this->quoteRepositoryMock
        );
    }

    public function testNoReferenceOrderIdThrowsException(): void
    {
        $this->requestMock->method('getParams')->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('No reference order id provided');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No reference order id provided');

        $this->controller->execute();
    }

    public function testNonExistentOrderThrowsException(): void
    {
        $this->requestMock->method('getParams')->willReturn([AntomConstants::REFERENCE_ORDER_ID => '100']);

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(null);

        $this->orderFactoryMock->method('create')->willReturn($orderMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Provided order id is not associated with an order!');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided order id is not associated with an order!');

        $this->controller->execute();
    }

    public function testSuccessfulPaymentRedirectsToSuccess(): void
    {
        $this->requestMock->method('getParams')->willReturn([AntomConstants::REFERENCE_ORDER_ID => '100']);

        $paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentMock->method('getAdditionalInformation')
            ->with(AntomConstants::PAYMENT_STATUS)
            ->willReturn(AntomConstants::SUCCESS);
        $paymentMock->method('getMethodInstance')->willReturnSelf();

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getId')->willReturn(1);
        $orderMock->method('getPayment')->willReturn($paymentMock);
        $orderMock->method('loadByIncrementId')->with('100')->willReturnSelf();

        $this->orderFactoryMock->method('create')->willReturn($orderMock);

        // ✅ 正确地配置 $this->storeManagerMock
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        // Result mock
        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Redirect::class);
        $resultMock->expects($this->once())
            ->method('setPath')
            ->with('checkout/onepage/success');

        $this->resultFactoryMock->method('create')->willReturn($resultMock);

        // 执行
        $this->controller->execute();
    }

}
