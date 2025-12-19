<?php

namespace Antom\Core\Gateway\Response\Handler;


use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class AmsPayRedirectResponseHandler implements HandlerInterface
{
    const SUCCESS_URL = 'checkout/onepage/success';
    const FAILURE_URL = 'checkout/onepage/failure';

    /**
     * @var UrlBuilder|\Magento\Framework\UrlInterface
     */
    private UrlInterface $urlBuilder;
    private $requestHelper;

    public function __construct(
        UrlInterface  $urlBuilder,
        RequestHelper $requestHelper
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->requestHelper = $requestHelper;
    }

    /**
     * Handle Antom pay result
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $payment->setAdditionalInformation(AntomConstants::RESULT_MESSAGE,
            $response[AntomConstants::RESULT][AntomConstants::RESULT_MESSAGE]);
        if (strcmp($payment->getMethodInstance()->getCode(), AntomConstants::MAGENTO_ALIPAY_CN) === 0) {
            $this->handleAlipayCn($payment, $response);
        }
        if (strcmp($payment->getMethodInstance()->getCode(), AntomConstants::MAGENTO_ANTOM_CARD) === 0) {
            $this->handleCardPayment($order, $payment, $response);
        }
    }

    /**
     * Handle alipay CN payment initiation result
     * @param $payment
     * @param $response
     * @return void
     */
    private function handleAlipayCn(InfoInterface $payment, array $response)
    {
        $resultStatus = $response[AntomConstants::RESULT][AntomConstants::RESULT_STATUS];
        $transactionStatus = AntomConstants::LOWER_CASE_SUCCESS;
        if (strcmp($resultStatus, AntomConstants::F) == 0) {
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_STATUS, AntomConstants::FAIL);
            if (!empty($response[AntomConstants::PAYMENT_ID])) {
                $payment->setTransactionId($response[AntomConstants::PAYMENT_ID]);
            }
            $payment->setIsTransactionClosed(true);
            $payment->setIsTransactionPending(false);
            $transactionStatus = AntomConstants::FAIL;
        } else {
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_ID, $response[AntomConstants::PAYMENT_ID]);
            $payment->setAdditionalInformation(
                AntomConstants::PAYMENT_REQUEST_ID,
                $response[AntomConstants::PAYMENT_REQUEST_ID]
            );
            $paymentAction = [
                AntomConstants::ACTION => AntomConstants::REDIRECT,
                AntomConstants::NORMAL_URL => $response[AntomConstants::NORMAL_URL]
            ];
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_METHOD, AntomConstants::MAGENTO_ALIPAY_CN);
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_ACTION, json_encode($paymentAction));
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_STATUS, AntomConstants::INITIATED);
            $payment->setTransactionId($response[AntomConstants::PAYMENT_ID]);
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
        }
        $this->fillTransactionDetails(
            $payment, $response, [AntomConstants::STATUS => $transactionStatus]);
        $payment->addTransaction(TransactionInterface::TYPE_ORDER, null, true);

    }






    /**
     * Handle Antom card payment authorization result
     * @param $order
     * @param $payment
     * @param $response
     * @return void
     */
    private function handleCardPayment($order, InfoInterface $payment, $response)
    {
        $resultStatus = $response[AntomConstants::RESULT][AntomConstants::RESULT_STATUS];
        if (strcmp($resultStatus, AntomConstants::S) == 0) {
            // When the payment is success, inactive quote and redirect to the success page
            // no paymentId for SUCCESS status
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_STATUS, AntomConstants::SUCCESS);
            if (!empty($response[AntomConstants::PAYMENT_ID])) {
                $payment->setAdditionalInformation(AntomConstants::PAYMENT_ID, $response[AntomConstants::PAYMENT_ID]);
                $payment->setTransactionId($response[AntomConstants::PAYMENT_ID]);
            }
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
        } else {
            // U or F
            $payment->setAdditionalInformation(AntomConstants::PAYMENT_STATUS, AntomConstants::FAIL);
            if (!empty($response[AntomConstants::PAYMENT_ID])) {
                $payment->setTransactionId($response[AntomConstants::PAYMENT_ID]);
            }
            $payment->setIsTransactionClosed(true);
            $payment->setIsTransactionPending(false);
        }
        // This only expects for failed case
        $this->fillTransactionDetails($payment, $response);
        $payment->addTransaction(TransactionInterface::TYPE_AUTH, null, true);
        $payment->setAdditionalInformation(AntomConstants::PAYMENT_METHOD, AntomConstants::MAGENTO_ANTOM_CARD);
    }

    /**
     * Fill in Transaction details, for now we fill in payment currency and payment amount
     * @param $payment
     * @param $response
     * @return void
     */
    private function fillTransactionDetails($payment, $response, $statusArray = null)
    {
        $resultArray = [];
        if (!empty($response[AntomConstants::PAYMENT_AMOUNT])) {
            $resultArray = array_merge($resultArray, $this->requestHelper->amountConvert($response[AntomConstants::PAYMENT_AMOUNT]));
        }
        if (!empty($statusArray)) {
            $resultArray = array_merge($resultArray, $statusArray);
        }
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $resultArray
        );
    }
}
