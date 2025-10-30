<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsPaymentAmountBuilder implements BuilderInterface
{
    /**
     * Currency map to minor unit digits
     */
    const CURRENCY_MINOR_UNIT = [
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'CNY' => 2,
        'AUD' => 2,
        'CAD' => 2,
        'JPY' => 0,
        'KRW' => 0,
        'VND' => 0,
        'KWD' => 3
    ];
    /**
     * @var RequestHelper
     */
    private $helper;

    /**
     * AmsPaymentAmountBuilder constructor
     * @param RequestHelper $helper
     */
    public function __construct(RequestHelper $helper)
    {
        $this->helper = $helper;
    }


    /**
     * Build payment amount, including currency and value
     * @param array $buildSubject
     * @return array<string, array<string, string>>
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $paymentAmount = $this->helper->getOrderAmount($order);
        return [
            AntomConstants::PAYMENT_AMOUNT => $paymentAmount
        ];
    }

}
