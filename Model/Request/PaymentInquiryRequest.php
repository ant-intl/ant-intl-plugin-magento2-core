<?php

namespace Antom\Core\Model\Request;

use \Request\AlipayRequest;

class PaymentInquiryRequest extends AlipayRequest
{
    /**
     * @var string
     */
    public $paymentId;

    /**
     * @var string
     */
    public $paymentRequestId;

    /**
     * Get value of paymentId
     *
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * Set value of paymentId
     *
     * @param string $paymentId
     */
    public function setPaymentId($paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Get value of paymentRequestId
     *
     * @return string
     */
    public function getPaymentRequestId()
    {
        return $this->paymentRequestId;
    }

    /**
     * Set value of paymentRequestId
     *
     * @param string $paymentRequestId
     * @return void
     */
    public function setPaymentRequestId($paymentRequestId): void
    {
        $this->paymentRequestId = $paymentRequestId;
    }

}
