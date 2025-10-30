<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsRedirectUrlBuilder implements BuilderInterface
{
    private const PAYMENT_REDIRECT_PATH = '/antom/redirect';

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * AmsRedirectUrlBuilder constructor
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * Build paymentRedirectUrl
     * @param array $buildSubject
     * @return string[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();
        $domain = rtrim($this->requestHelper->getDomain(), '/');

        switch ($order->getPayment()->getMethod()) {
            case AntomConstants::MAGENTO_ALIPAY_CN:
                $paymentRedirectUrl = $domain . self::PAYMENT_REDIRECT_PATH . '?referenceOrderId=' . $orderId;
                break;
            case AntomConstants::MAGENTO_ANTOM_CARD:
                $paymentRedirectUrl = 'https://checkout.antom.com/checkout-page/pages/close-event/index.html';
                break;
            default:
                $paymentRedirectUrl = $domain . self::PAYMENT_REDIRECT_PATH . '?referenceOrderId=' . $orderId;
        }

        return [
            AntomConstants::PAYMENT_REDIRECT_URL => $paymentRedirectUrl
        ];
    }

}
