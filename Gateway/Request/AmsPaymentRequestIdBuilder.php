<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsPaymentRequestIdBuilder implements BuilderInterface
{
    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * AmsPaymentRequestIdBuilder constructor
     * @param $logger
     */
    public function __construct(AntomLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Build PaymentRequestId
     * @param array $buildSubject
     * @return string[]
     */
    public function build(array $buildSubject): array
    {
        // 1. Read the payment data object from the build subject
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        // 2. Get the payment instance
        $payment = $paymentDataObject->getPayment();
        if ($payment->getData(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)) {
            // Value exists and is not empty (not null, false, 0, '', etc.)
            // for card element sdk, we have the payment_request_id passing from UI to updatePaymentSession
            return [
                AntomConstants::PAYMENT_REQUEST_ID => $payment->getData(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ];
        }

        $microtime = microtime(true);
        $milliseconds = (int)(($microtime - floor($microtime)) * 1000);
        $datetime = date('YmdHis');
        $datetimeWithMs = $datetime . sprintf('%03d', $milliseconds);

        $order = $paymentDataObject->getOrder();
        $orderId = $order->getOrderIncrementId();
        $storeId = $order->getStoreId();

        $randomNumber = 'RN';
        try {
            $randomNumber = random_int(10, 99);
        } catch (\Throwable $e) {
            $this->logger->addAntomWarning('Fail to generate random number, use default value [RN], error: '
                . $e->getMessage());
        }
        $paymentRequestId = $orderId . AntomConstants::UNDERSCORE . $datetimeWithMs . $storeId . $randomNumber;
        // Set the paymentRequestId to the payment object for later validating the response from Antom.
        $payment->setAdditionalInformation(AntomConstants::PAYMENT_REQUEST_ID, $paymentRequestId);
        return [
            AntomConstants::PAYMENT_REQUEST_ID => $paymentRequestId
        ];
    }
}
