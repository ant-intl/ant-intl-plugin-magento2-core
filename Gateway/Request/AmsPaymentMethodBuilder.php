<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsPaymentMethodBuilder implements BuilderInterface
{
    /**
     * Map the Magento payment method to Antom payment method
     */
    private const ANTOM_PAYMENT_METHOD_MAPPING = [
        'antom_alipay_cn' => 'ALIPAY_CN',
        'antom_card' => "CARD"
    ];

    /**
     * Build paymentMethod for Antom ams pay
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentMethod = [];
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $magentoPaymentMethod = $payment->getMethod();
        $paymentMethodType = self::ANTOM_PAYMENT_METHOD_MAPPING[$magentoPaymentMethod];
        if (strcasecmp($paymentMethodType, 'CARD') == 0) {
            $paymentMethod[AntomConstants::PAYMENT_METHOD_ID] = $payment->getAdditionalInformation(AntomConstants::CARD_TOKEN);
        }
        // We can safely unset here even if the field does not exist in additionalInformation
        $additionalInformation = $payment->getAdditionalInformation();
        unset($additionalInformation[AntomConstants::CARD_TOKEN]);
        $payment->setAdditionalInformation($additionalInformation);
        
        $paymentMethod[AntomConstants::PAYMENT_METHOD_TYPE] = $paymentMethodType;
        return [
            AntomConstants::PAYMENT_METHOD => $paymentMethod
        ];
    }
}
