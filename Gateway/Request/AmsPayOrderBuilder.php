<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;

class AmsPayOrderBuilder implements BuilderInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AmsPayOrderBuilder constructor
     * @param RequestHelper $requestHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestHelper         $requestHelper,
        StoreManagerInterface $storeManager,
        CustomerSession       $customerSession,
        CheckoutSession       $checkoutSession,
    )
    {
        $this->requestHelper = $requestHelper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Build order information
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $orderId = $order->getOrderIncrementId();
        $orderAmount = $this->requestHelper->getOrderAmount($order);
        $storeId = $order->getStoreId();
        $orderDescription = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $goods = [];

        $referenceBuyerId = $this->getUserIdentifier();
        foreach ($order->getItems() as $orderItem) {
            $goods[] = [
                AntomConstants::REFERENCE_GOODS_ID => $orderItem->getProductId(),
                AntomConstants::GOODS_NAME => $orderItem->getName(),
                AntomConstants::GOODS_QUANTITY => $orderItem->getQtyOrdered(),
                AntomConstants::GOODS_CATEGORY => $orderItem->getProductType(),
            ];
        }
        return [
            AntomConstants::ORDER => [
                AntomConstants::ORDER_AMOUNT => $orderAmount,
                AntomConstants::REFERENCE_ORDER_ID => $orderId,
                AntomConstants::ORDER_DESCRIPTION => $orderDescription,
                AntomConstants::GOODS => $goods,
                AntomConstants::REFERENCE_BUYER_ID => $referenceBuyerId,
            ]
        ];
    }

    /**
     * Get current user's identifier
     *
     * @return string if user is logged in, return customer id, else user is guest, return the guest's email
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getUserIdentifier(): string
    {
        if ($this->customerSession->isLoggedIn()) {
            return (string)$this->customerSession->getCustomerId();
        }
        $quote = $this->checkoutSession->getQuote();
        return $quote->getCustomerEmail();
    }
}
