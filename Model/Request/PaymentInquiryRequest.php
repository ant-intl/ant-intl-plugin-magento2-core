<?php

namespace Antom\Core\Model\Request;

use \Request\AlipayRequest;

class PaymentInquiryRequest extends AlipayRequest
{
    public $paymentId;

    /**
     * @return mixed
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param mixed $paymentId
     */
    public function setPaymentId($paymentId): void
    {
        $this->paymentId = $paymentId;
    }

}
