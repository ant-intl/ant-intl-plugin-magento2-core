<?php

namespace Antom\Core\Observer;

use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class QuoteStatusObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var  Order $order */
        $order = $observer->getEvent()->getOrder();

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $paymentAction = $payment->getAdditionalInformation(AntomConstants::PAYMENT_ACTION);
        $paymentStatus = $payment->getAdditionalInformation(AntomConstants::PAYMENT_STATUS);
        $isInitiatedSuccess = !empty($paymentStatus)
            && strcmp($paymentStatus, AntomConstants::INITIATED) == 0
            && !empty($paymentAction)
            && json_decode($paymentAction, true)[AntomConstants::ACTION] == AntomConstants::REDIRECT;
        $isPaymentFail = !empty($paymentStatus)
            && strcmp($paymentStatus, AntomConstants::FAIL) == 0;
        // TODO: add card payment quote restore for failed cases
        // For the Alipay CN redirect mode and Fail scenario, we should keep the quote
        if ($isInitiatedSuccess || $isPaymentFail) {
            // Further shopper action required (e.g. redirect or 3DS authentication)
            /** @var Quote $quote */
            $quote = $observer->getEvent()->getQuote();
            // Keep cart active until such actions are taken
            $quote->setIsActive(true);
        }
    }

}
