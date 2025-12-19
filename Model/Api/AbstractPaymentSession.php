<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Gateway\AntomPathConstants;
use Antom\Core\Helper\RequestHelper;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Request\AlipayCreatePaymentSessionRequest;
use Antom\Core\Model\Request\ExtendInfo;
use Client\DefaultAlipayClient;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Store\Model\StoreManagerInterface;
use Model\Amount;
use Model\Buyer;
use Model\Order;
use Model\PaymentFactor;
use Model\PaymentMethod;
use Model\ProductCodeType;
use Model\SettlementStrategy;

abstract class AbstractPaymentSession
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var RequestHelper
     */
    private $requestHelper;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
    /**
     * @var AntomConfig
     */
    private $antomConfig;
    /**
     * @var AntomLogger
     */
    private $antomLogger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * Constructor for AbstractPaymentSession
     *
     * @param CartRepositoryInterface $cartRepository
     * @param RequestHelper $requestHelper
     * @param StoreManagerInterface $storeManager
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param AntomConfig $antomConfig
     * @param UserContextInterface $userContext
     * @param AntomLogger $antomLogger
     */
    public function __construct(CartRepositoryInterface $cartRepository,
                                RequestHelper $requestHelper,
                                StoreManagerInterface $storeManager,
                                MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
                                AntomConfig $antomConfig,
                                UserContextInterface $userContext,
                                AntomLogger $antomLogger){
        $this->cartRepository = $cartRepository;
        $this->requestHelper = $requestHelper;
        $this->storeManager = $storeManager;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->antomConfig = $antomConfig;
        $this->userContext = $userContext;
        $this->antomLogger = $antomLogger;
    }

    /**
     * Helper method for both Guest cart and logged-in user's cart
     *
     * @param string $cartId
     * @param string $userIdentifier either guest email or current logged in userId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createPaymentSessionHelper(string $cartId, string $userIdentifier)
    {
        $request = new AlipayCreatePaymentSessionRequest();

        $quote = $this->getQuoteByCartId($cartId);

        if (!$quote || $quote->getItemsCount() == 0) {
            throw new LocalizedException(__('Cannot create payment session: cart is empty.'));
        }
        // set order and order.buyer
        $paymentRequestId = $this->generatePaymentRequestId($quote);
        // setup order
        $order = new Order();
        $orderAmount = new Amount();
        $value = $this->requestHelper->getQuoteAmount($quote);
        $orderAmount->setCurrency($quote->getCurrency()->getQuoteCurrencyCode());
        $orderAmount->setValue($value);
        $order->setOrderAmount($orderAmount);
        $orderDescription = $this->storeManager->getStore($quote->getStoreId())->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $order->setOrderDescription($orderDescription);
        $order->setReferenceOrderId($quote->getReservedOrderId());
        $buyer = new Buyer();
        $buyer->setReferenceBuyerId($userIdentifier);
        $order->setBuyer($buyer);

        $request->setOrder($order);

        $paymentAmount = new Amount();
        $paymentAmount->setCurrency($quote->getCurrency()->getQuoteCurrencyCode());
        $paymentAmount->setValue($value);
        $request->setPaymentAmount($paymentAmount);

        $domain = $this->requestHelper->getDomain();
        $paymentNotifyUrl = $domain . AntomPathConstants::CONTROLLER_PATH;
        // set to random value and not being used
        $paymentRedirectUrl = "{$domain}/checkout/success";

        $request->setPaymentNotifyUrl($paymentNotifyUrl);
        $request->setPaymentRedirectUrl($paymentRedirectUrl);
        $env = $this->requestHelper->composeEnvInfo();
        $request->setEnv($env);
        $settlementStrategy = new SettlementStrategy();
        $settlementStrategy->setSettlementCurrency("USD");
        $request->setSettlementStrategy($settlementStrategy);

        $paymentMethod = new PaymentMethod();
        $paymentMethod->setPaymentMethodType("CARD");
        $request->setPaymentMethod($paymentMethod);
        // set paymentFactor
        $paymentFactor = new PaymentFactor();
        $paymentFactor->setIsAuthorization(true);
        $request->setPaymentFactor($paymentFactor);

        $request->setProductCode(ProductCodeType::CASHIER_PAYMENT);
        $request->setProductScene("ELEMENT_PAYMENT");

        $storeId = $quote->getStoreId();
        $gatewayUrl = $this->antomConfig->getAntomGatewayUrl($storeId);
        $merchantPrivateKey = $this->antomConfig->getMerchantPrivateKey($storeId);
        $antomPublicKey = $this->antomConfig->getAntomPublicKey($storeId);
        $clientId = $this->antomConfig->getAntomClientId($storeId);
        $alipayClient = $this->createAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId);
        $request->setClientId($clientId);
        $request->setPaymentRequestId($paymentRequestId);
        $supportedCards = $this->antomConfig->getAMSEnabledCards();
        $extendInfo = new ExtendInfo("MAGENTO", $supportedCards);
        $request->setExtendInfo($extendInfo);

        $request->setRequireUpdateAndConfirm(true);

        $alipayResponse = $alipayClient->execute($request);

        $alipayResponse->paymentRequestId = $paymentRequestId;
        $alipayResponseDecoded = json_decode(json_encode($alipayResponse), true);
        $this->antomLogger->addAntomInfoLog("CreatePaymentSession response: ", $alipayResponseDecoded);
        return json_encode($alipayResponse);
    }


    function getQuoteByCartId($cartId)
    {
        $userType = $this->userContext->getUserType();

        if ($userType === UserContextInterface::USER_TYPE_CUSTOMER) {
            $customerId = $this->userContext->getUserId();
            $quote = $this->cartRepository->getActiveForCustomer($customerId);
            if ((int)$quote->getId() !== (int)$cartId) {
                throw new AuthorizationException(__('Cart does not belong to current customer.'));
            }
        } elseif ($userType === UserContextInterface::USER_TYPE_GUEST) {

            try {
                $qid = $this->maskedQuoteIdToQuoteId->execute($cartId);
            } catch (\Exception $e) {
                throw new NoSuchEntityException(__('Guest cartId is invalid.'));
            }

            if (!$qid) {
                throw new NoSuchEntityException(__('Guest cartId is invalid.'));
            }
            $quote = $this->cartRepository->get($qid);

            if ($quote->getCustomerId()) {
                throw new NoSuchEntityException(__('Quote does not belong to a guest.'));
            }
        } else {
            throw new AuthorizationException(__('Unauthorized.'));
        }
        return $quote;
    }


    private function generatePaymentRequestId($quote) {
        $microtime = microtime(true);
        $milliseconds = (int)(($microtime - floor($microtime)) * 1000);
        $datetime = date('YmdHis');
        $datetimeWithMs = $datetime . sprintf('%03d', $milliseconds);
        $randomNumber = 'RN';
        try {
            $randomNumber = random_int(10, 99);
        } catch (\Throwable $e) {
            $this->logger->addAntomWarning('Fail to generate random number, use default value [RN], error: '
                . $e->getMessage());
        }
        return  $this->generateDraftOrderId() . AntomConstants::UNDERSCORE . $datetimeWithMs . $quote->getStoreId() . $randomNumber;
    }

    private function generateDraftOrderId()
    {
        try {
            $random = bin2hex(random_bytes(4)); // 8位十六进制
            return 'D' . strtoupper($random);
        } catch (\Exception $e) {
            // 备用方案
            return 'D' . strtoupper(substr(md5(uniqid()), 0, 8));
        }
    }

    /**
     * Wrapper method for creating Alipay client (for testing)
     */
    protected function createAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId)
    {
        return new DefaultAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId);
    }
}
