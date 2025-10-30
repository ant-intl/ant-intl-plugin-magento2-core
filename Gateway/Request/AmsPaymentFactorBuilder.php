<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsPaymentFactorBuilder implements BuilderInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * AmsPaymentFactorBuilder constructor
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * Build paymentFactor
     * @param array $buildSubject
     * @return array|array[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $magentoPaymentMethod = $payment->getMethod();
        if ($this->requestHelper->isAlipayCnPaymentMethod($magentoPaymentMethod)) {
            return [];
        }

        if ($this->requestHelper->isCardPaymentMethod($magentoPaymentMethod)) {
            return [
                    AntomConstants::PAYMENT_FACTOR => [
                        // For CARD, paymentFactor.isAuthorization needs to set to true
                        // paymentFactor.captureMode default is AUTOMATIC
                    AntomConstants::IS_AUTHORIZATION => true
                ]
            ];
        }

        return [
            AntomConstants::PAYMENT_FACTOR => [
                AntomConstants::IS_AUTHORIZATION => false
            ]
        ];
    }

}
