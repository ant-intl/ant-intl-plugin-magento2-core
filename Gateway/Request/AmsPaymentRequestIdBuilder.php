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
        $microtime = microtime(true);
        $milliseconds = (int)(($microtime - floor($microtime)) * 1000);
        $datetime = date('YmdHis');
        $datetimeWithMs = $datetime . sprintf('%03d', $milliseconds);
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $orderId = $order->getOrderIncrementId();
        $storeId = $order->getStoreId();
        $payment = $paymentDataObject->getPayment();
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
