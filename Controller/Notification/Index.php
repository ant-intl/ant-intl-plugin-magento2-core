<?php

namespace Antom\Core\Controller\Notification;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use Antom\Core\Logger\AntomLogger;
use Client\SignatureTool;
use InvalidArgumentException;
use Magento\Checkout\Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderMutex;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\DependencyInjection\Exception\EmptyParameterValueException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction;

/*
 * Antom notification webhook callback
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var StatusResolver
     */
    private $statusResolver;
    /**
     * @var SignatureTool
     */
    private $signatureTool;
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;
    /**
     * @var AntomConfig
     */
    private $antomConfig;
    /**
     * @var AntomLogger
     */
    private $antomLogger;
    /**
     * @var OrderMutex
     */
    private $orderMutex;
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var Builder
     */
    private $transactionBuilder;

    private $helper;

    public function __construct(
        RequestInterface               $request,
        ResultFactory                  $resultFactory,
        OrderFactory                   $orderFactory,
        OrderRepository                $orderRepository,
        StoreManagerInterface          $storeManager,
        QuoteRepository                $quoteRepository,
        StatusResolver                 $statusResolver,
        SignatureTool                  $signatureTool,
        TransactionFactory             $transactionFactory,
        AntomConfig                    $antomConfig,
        AntomLogger                    $antomLogger,
        OrderMutex                     $orderMutex,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        Builder                        $transactionBuilder,
        RequestHelper                  $requestHelper
    )
    {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->statusResolver = $statusResolver;
        $this->signatureTool = $signatureTool;
        $this->transactionFactory = $transactionFactory;
        $this->antomConfig = $antomConfig;
        $this->antomLogger = $antomLogger;
        $this->orderMutex = $orderMutex;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionBuilder = $transactionBuilder;
        $this->helper = $requestHelper;
    }

    public function execute()
    {
        // validate request header
        $this->validateHeaders();

        // signature verification
        $this->verifySignature();

        // validate request body
        $this->validateReqBody();

        // handle the payment notification
        $this->handlePaymentNotify();

        return $this->assembleResult();
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Handle the Antom payment notification
     * @return void
     */
    private function handlePaymentNotify()
    {
        $bodyStr = $this->request->getContent();
        $body = json_decode($bodyStr, true);
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::PAYMENT_RESULT) {
            $order = $this->orderFactory->create()->loadByIncrementId($this->fetchOrderId());
        } else {
            $transaction = $this->fetchOrderPaymentTransaction($body);
            $order = $this->orderRepository->get($transaction->getOrderId());
        }
        // todo: add cron job when acquiring mutex fail
        $this->orderMutex->execute(
            $order->getId(),
            \Closure::fromCallable([$this, 'handleNotifyWithMutex']),
            [$order, $body]
        );
    }

    private function handleNotifyWithMutex($order, $body)
    {
        if (strcmp($body[AntomConstants::NOTIFY_TYPE], AntomConstants::PAYMENT_PENDING) === 0) {
            return;
        } elseif (strcmp($body[AntomConstants::NOTIFY_TYPE], AntomConstants::CAPTURE_RESULT) === 0) {
            // Handle notifyCapture for Antom CARD payment
            $this->handleNotifyCapture($order, $body);
        } elseif (strcmp($body[AntomConstants::NOTIFY_TYPE], AntomConstants::REFUND_RESULT) === 0) {
            // todo: to be implemented with refund functionality
        } elseif (strcmp($body[AntomConstants::NOTIFY_TYPE], AntomConstants::PAYMENT_RESULT) === 0) {
            if (strcmp($body[AntomConstants::RESULT][AntomConstants::RESULT_STATUS], AntomConstants::S) === 0) {
                // Handle the payment success case
                // For Alipay_CN, this is the final notification
                // For CARD payment, this is the notification for authorization
                $this->handleNotifySuccess($order, $body);
            } elseif (strcmp($body[AntomConstants::RESULT][AntomConstants::RESULT_STATUS], AntomConstants::F) === 0) {
                // handle the case that the payment with Antom failed, do not inactivate the quote in this case.
                $this->handleNotifyClosed($order, $body);
            }
        }
        $this->orderRepository->save($order);
    }

    private function handleNotifySuccess(Order $order, array $body)
    {
        $this->inactiveQuoteIfNecessary($order->getQuoteId());
        $payment = $order->getPayment();

        if ($this->checkInvalidOrderState($order, $payment, $body)) {
            return;
        }
        // update order state and status
        $this->propelOrderStateAndStatus($order, Order::STATE_PROCESSING, AntomConstants::SUCCESS, $body);
        // update sales_payment_transaction
        // Only need to insert capture record for alipay_cn
        // Card payment will have capture notify to update the transaction info
        if ($payment->getMethod() === AntomConstants::MAGENTO_ALIPAY_CN) {
            $this->insertCaptureTransaction($order, $body);
        }
    }

    /**
     * Set the Quote status to inactive if necessary
     * @param int|null $quoteId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function inactiveQuoteIfNecessary(int|null $quoteId)
    {
        if (!isset($quoteId)) {
            return;
        }
        // inactive quote if still active
        $quote = $this->quoteRepository->get($quoteId);
        if ($quote->getIsActive() === 1) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
        }
    }

    /**
     * Update the order state and status, also update Antom paymentStatus
     * @param Order $order
     * @param $orderState
     * @param $paymentStatus
     * @param $body
     * @return void
     * @throws LocalizedException
     */
    private function propelOrderStateAndStatus(Order $order, $orderState, $paymentStatus, $body)
    {
        $orderStatus = $this->statusResolver->getOrderStatusByState(
            $order,
            $orderState
        );
        $order->setState($orderState);
        $order->setStatus($orderStatus);
        $order->getPayment()->setAdditionalInformation(AntomConstants::PAYMENT_STATUS, $paymentStatus);
        $order->addCommentToStatusHistory(
            'payment status: ' . $body[AntomConstants::RESULT][AntomConstants::RESULT_MESSAGE]
        );
        if (in_array($orderStatus, [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_HOLDED])) {
            $order->registerCancellation("Payment not Success!");
        }
    }

    /**
     * Insert a Capture Transaction Record
     * @param Order $order
     * @param array $body
     * @param string $status
     * @return void
     * @throws LocalizedException
     */
    private function insertCaptureTransaction(Order $order, array $body, string $status = 'success')
    {
        $payment = $order->getPayment();
        $transaction = $this->fetchOrderPaymentTransaction($body);
        $payment->setParentTransactionId($body[AntomConstants::PAYMENT_ID]);
        $payment->setTransactionId(
            $body[AntomConstants::CAPTURE_ID] ?? 'capture_' . $body[AntomConstants::PAYMENT_ID]
        );
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
        $payment->setShouldCloseParentTransaction(false);
        $amountArray = [];
        if (isset($body[AntomConstants::CAPTURE_AMOUNT])) {
            $amountArray = $body[AntomConstants::CAPTURE_AMOUNT];
        }
        if (isset($body[AntomConstants::PAYMENT_AMOUNT])) {
            $amountArray = $body[AntomConstants::PAYMENT_AMOUNT];
        }
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            array_merge(
                $this->helper->amountConvert($amountArray),
                ['status' => $status]
            )
        );
        $captureTransaction = $payment->addTransaction(TransactionInterface::TYPE_CAPTURE);
        $captureTransaction->setParentId($transaction->getId());
    }

    /**
     * Check if the order state is invalid for payment success notify
     * @param Order $order
     * @param Order\Payment $payment
     * @param array $body
     * @return bool
     */
    private function checkInvalidOrderState(Order $order, Order\Payment $payment, array $body): bool
    {
        $currentPaymentStatus = $payment->getAdditionalInformation(AntomConstants::PAYMENT_STATUS);
        $isAlipayCnNotInitiated = (
            $payment->getMethod() === AntomConstants::MAGENTO_ALIPAY_CN
            && $currentPaymentStatus != AntomConstants::INITIATED
        );

        $isInvalidOrderState = !in_array(
            $order->getState(),
            [Order::STATE_PENDING_PAYMENT, Order::STATE_NEW]
        );
        $invalid = $isInvalidOrderState || $isAlipayCnNotInitiated;
        if ($invalid) {
            $msg = sprintf(
                "The order status is not new or pending_payment, ignore this webhook.\nRequest body=[%s]"
                , json_encode($body)
            );
            $this->antomLogger->addAntomInfoLog($msg);
        }
        return $invalid;
    }

    /**
     * @param array $body
     * @return false|mixed
     * @throws LocalizedException
     */
    private function fetchOrderPaymentTransaction(array $body)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('txn_id', $body[AntomConstants::PAYMENT_ID])
            ->create();
        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();
        if (count($transactions) !== 1 ||
            !in_array(reset($transactions)->getTxnType(), [TransactionInterface::TYPE_AUTH, TransactionInterface::TYPE_ORDER])) {
            $errorMsg = sprintf(
                "Can not find the authorization record with paymentId = %s",
                $body[AntomConstants::PAYMENT_ID]
            );
            $this->antomLogger->error($errorMsg);
            throw new LocalizedException(__($errorMsg));
        }
        return reset($transactions);
    }

    /**
     * Handle notifyCapture webhook
     * @param Order $order
     * @param array $body
     * @return void
     * @throws LocalizedException
     */
    private function handleNotifyCapture(Order $order, array $body)
    {
        $captureStatus = $body[AntomConstants::RESULT][AntomConstants::RESULT_STATUS];
        $captureTransactionStatus = strcmp($captureStatus, AntomConstants::S) === 0
            ? 'success'
            : 'failure';
        $this->insertCaptureTransaction($order, $body, $captureTransactionStatus);
        if ($captureTransactionStatus == 'failure') {
            $this->propelOrderStateAndStatus($order, Order::STATE_CLOSED, AntomConstants::FAIL, $body);
        }
    }

    /**
     * Handle order close notify webhook
     * @param Order $order
     * @return void
     */
    private function handleNotifyClosed(Order $order, array $body)
    {
        $payment = $order->getPayment();
        // Deactivate Quote if still active
        $this->inactiveQuoteIfNecessary($order->getQuoteId());
        // Check if the order status is valid to update
        $invalid = $this->checkInvalidOrderState($order, $payment, $body);
        if ($invalid) {
            return;
        }
        // Propel the Order status with the Payment status fail.
        $this->propelOrderStateAndStatus($order, Order::STATE_CLOSED, AntomConstants::FAIL, $body);
        // update sales_order_payment additional info.
        $payment->setAdditionalInformation(AntomConstants::PAYMENT_RESULT, json_encode($body[AntomConstants::RESULT]));
        // update order_payment_transaction status
        $payment->setTransactionId($body[AntomConstants::PAYMENT_ID]);
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS, array_merge(
                $this->helper->amountConvert($body[AntomConstants::PAYMENT_AMOUNT]),
                ['status' => 'failure'],
                ['message' => $body[AntomConstants::RESULT][AntomConstants::RESULT_MESSAGE]]
            )
        );
        $transactionType = TransactionInterface::TYPE_ORDER;
        if ($payment->getMethod() === AntomConstants::MAGENTO_ANTOM_CARD) {
            $transactionType = TransactionInterface::TYPE_AUTH;
        }
        $payment->addTransaction($transactionType);
    }

    /**
     * Check the required fields in the request body
     * @return void
     */
    private function validateReqBody()
    {
        $bodyStr = $this->request->getContent();
        $body = json_decode($bodyStr, true);
        if (empty($body)) {
            $this->antomLogger->error("Missing body");
            throw new InvalidArgumentException('Missing body');
        }
        if (empty($body[AntomConstants::NOTIFY_TYPE])) {
            $this->antomLogger->error("Missing notifyType");
            throw new InvalidArgumentException('Missing notifyType');
        }
        if (empty($body[AntomConstants::RESULT])) {
            $this->antomLogger->error("Missing result");
            throw new InvalidArgumentException('Missing result');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::PAYMENT_RESULT
            && empty($body[AntomConstants::PAYMENT_REQUEST_ID])) {
            $this->antomLogger->error("Missing paymentRequestId");
            throw new InvalidArgumentException('Missing paymentRequestId');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::CAPTURE_RESULT
            && empty($body[AntomConstants::CAPTURE_REQUEST_ID])) {
            $this->antomLogger->error("Missing captureRequestId");
            throw new InvalidArgumentException('Missing captureRequestId');
        }
        if (empty($body[AntomConstants::PAYMENT_ID])) {
            $this->antomLogger->error("Missing paymentId");
            throw new InvalidArgumentException('Missing paymentId');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::PAYMENT_RESULT
            && empty($body[AntomConstants::PAYMENT_AMOUNT])) {
            $this->antomLogger->error("Missing paymentAmount");
            throw new InvalidArgumentException('Missing paymentAmount');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::CAPTURE_RESULT
            && empty($body[AntomConstants::CAPTURE_AMOUNT])) {
            $this->antomLogger->error("Missing captureAmount");
            throw new InvalidArgumentException('Missing captureAmount');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::PAYMENT_RESULT
            && empty($body[AntomConstants::PAYMENT_CREATE_TIME])) {
            $this->antomLogger->error("Missing paymentCreateTime");
            throw new InvalidArgumentException('Missing paymentCreateTime');
        }
        if ($body[AntomConstants::NOTIFY_TYPE] === AntomConstants::CAPTURE_RESULT) {
            return;
        }
        // check if the paymentId matches the one store in the table 'sales_order_payment'
        $orderId = $this->fetchOrderId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $orderPaymentId = $payment->getAdditionalInformation(AntomConstants::PAYMENT_ID);
        if (strcmp($orderPaymentId, $body[AntomConstants::PAYMENT_ID]) != 0) {
            $this->antomLogger->error("Invalid paymentId, not matching the paymentId in the order");
            throw new InvalidArgumentException('Invalid paymentId, not matching the paymentId in the order');
        }
    }

    /**
     * Fetch the orderId
     * @return mixed|string
     */
    private function fetchOrderId()
    {
        $orderId = $this->request->getParam(AntomConstants::REFERENCE_ORDER_ID);
        if (empty($orderId)) {
            $body = json_decode($this->request->getContent(), true);
            $paymentRequestId = $body[AntomConstants::PAYMENT_REQUEST_ID];
            $orderId = explode(AntomConstants::UNDERSCORE, $paymentRequestId)[0];
        }
        return $orderId;
    }

    /**
     * Verify the signature in the payment notification
     * @return void
     */
    private function verifySignature()
    {
        $signature = $this->request->getHeader(AntomConstants::SIGNATURE);
        // use test_signature for local debug
        // todo: remove before go public
        if (strcmp($signature, 'test_signature') == 0) {
            return;
        }
        $signatureVal = $this->extractSignatureValue($signature);
        $clientId = $this->request->getHeader(AntomConstants::CLIENT_ID);
        $httpMethod = $this->request->getMethod();
        $path = $this->request->getPathInfo();
        $reqTime = $this->request->getHeader(AntomConstants::REQUEST_TIME);
        $reqBodyContent = $this->request->getContent();
        $data = json_decode($reqBodyContent);
        $reqBody = json_encode($data);
        $storeId = $this->storeManager->getStore()->getId();
        $antomPublicKey = $this->antomConfig->getAntomPublicKey($storeId);
        $verifyResult = $this->signatureTool->verify(
            $httpMethod,
            $path,
            $clientId,
            $reqTime,
            $reqBody,
            $signatureVal,
            $antomPublicKey
        );
        if ($verifyResult === false) {
            $this->antomLogger->addAntomWarning("Error occurred during signature verification.");
            throw new Exception(__("Error occurred during signature verification."));
        } elseif ($verifyResult === 0) {
            $this->antomLogger->addAntomWarning("Signature verification failed.");
            throw new Exception(__("Signature verification failed."));
        }
    }

    /**
     * Extract the signature value from Antom signature.
     * @param string $signature
     * @return string
     */
    private function extractSignatureValue(string $signature)
    {
        $needle = ',signature=';
        $pos = strpos($signature, $needle);
        return substr($signature, $pos + strlen($needle));
    }

    /**
     * Validate the Antom payment notification Headers
     * check all the required fields
     * @return void
     */
    private function validateHeaders()
    {
        $headers = $this->request->getHeaders()->toArray();
        if (empty($headers)) {
            $this->antomLogger->error(__('Headers not set'));
            throw new EmptyParameterValueException('Headers not set');
        }
        if (empty($this->request->getHeader(AntomConstants::SIGNATURE))) {
            $this->antomLogger->error(__('Signature not set'));
            throw new EmptyParameterValueException('Signature not set');
        }
        if (empty($this->request->getHeader(AntomConstants::CLIENT_ID))) {
            $this->antomLogger->error(__('Client ID not set'));
            throw new EmptyParameterValueException('Client ID not set');
        }
        if (empty($this->request->getHeader(AntomConstants::REQUEST_TIME))) {
            $this->antomLogger->error(__('Request time not set'));
            throw new EmptyParameterValueException('Request time not set');
        }
        $storeId = $this->storeManager->getStore()->getId();
        $clientId = $this->antomConfig->getAntomClientId($storeId);
        if (strcmp($this->request->getHeader(AntomConstants::CLIENT_ID), $clientId) != 0) {
            $this->antomLogger->error(__('Client ID not matched'));
            throw new InvalidArgumentException('Client ID not matched');
        }
    }

    /**
     * Assemble result for Antom payment notification
     * @return ResultInterface
     */
    private function assembleResult(): ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $data = [
            AntomConstants::RESULT => [
                AntomConstants::RESULT_CODE => AntomConstants::SUCCESS,
                AntomConstants::RESULT_STATUS => AntomConstants::S,
                AntomConstants::RESULT_MESSAGE => AntomConstants::LOWER_CASE_SUCCESS
            ]
        ];
        $result->setData($data);
        return $result;
    }
}
