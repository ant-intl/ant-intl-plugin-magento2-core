<?php

namespace Antom\Core\Controller\Redirect;

use InvalidArgumentException;
use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\PaymentStatusHelper;
use Antom\Core\Logger\AntomLogger;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Redirect url to decide payment result page
 */
class Index implements HttpGetActionInterface
{
    const SUCCESS_URL = 'checkout/onepage/success';
    const FAILURE_URL = 'checkout/onepage/failure';
    const WAITING_URL = 'antom/payment/waiting';

    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var AntomConfig
     */
    private $config;
    /**
     * @var AntomLogger
     */
    private $antomLogger;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var PaymentStatusHelper
     */
    private $paymentStatusHelper;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        OrderFactory            $orderFactory,
        StoreManagerInterface   $storeManager,
        AntomConfig             $config,
        AntomLogger             $antomLogger,
        Session                 $session,
        CartRepositoryInterface $cartRepository,
        ResultFactory           $resultFactory,
        RequestInterface        $request,
        PaymentStatusHelper     $paymentStatusHelper,
        QuoteRepository         $quoteRepository
    )
    {
        $this->config = $config;
        $this->antomLogger = $antomLogger;
        $this->session = $session;
        $this->cartRepository = $cartRepository;
        $this->orderFactory = $orderFactory;
        $this->storeManager = $storeManager;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->paymentStatusHelper = $paymentStatusHelper;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        // TODO: make sure card payment not being redirected
        $redirectParam = $this->request->getParams();
        // check the required parameter in the redirect request
        if (empty($redirectParam) || empty($redirectParam[AntomConstants::REFERENCE_ORDER_ID])) {
            $this->antomLogger->error('No reference order id provided');
            throw new InvalidArgumentException(__('No reference order id provided'));
        }
        $referenceOrderId = trim($redirectParam[AntomConstants::REFERENCE_ORDER_ID], '/');
        $order = $this->orderFactory->create()->loadByIncrementId($referenceOrderId);
        if (!$order || !$order->getId()) {
            $this->antomLogger->error('Provided order id is not associated with an order!');
            throw new InvalidArgumentException(__('Provided order id is not associated with an order!'));
        }
        $payment = $order->getPayment();
        if (!$payment) {
            $this->antomLogger->error('Order has no payment information');
            throw new InvalidArgumentException(__('Order has no payment information'));
        }
        $storeId = $this->storeManager->getStore()->getId();
        $paymentStatus = $payment->getAdditionalInformation(AntomConstants::PAYMENT_STATUS);
        if (strcmp($paymentStatus, AntomConstants::INITIATED) == 0
            || ($payment->getMethod()==AntomConstants::MAGENTO_ANTOM_CARD
                && strcmp($paymentStatus, AntomConstants::SUCCESS) == 0)) {
            // payment has been initiated, but the payment notification has not received yet
            // query Antom for the final payment status
            $paymentId = $payment->getAdditionalInformation(AntomConstants::PAYMENT_ID);
            $paymentRequestId = $payment->getAdditionalInformation(AntomConstants::ANTOM_PAYMENT_REQUEST_ID);
            $inquiryResult = $this->inquiryPaymentStatus($storeId, $paymentId, $paymentRequestId);
            if ($inquiryResult === AntomConstants::UNKNOWN) {
                //todo: 查不到状态如何跳转
                switch ($payment->getMethod()) {
                    case AntomConstants::MAGENTO_ALIPAY_CN:
                        $url = self::FAILURE_URL;
                        break;
                    case AntomConstants::MAGENTO_ANTOM_CARD:
                        $url = self::WAITING_URL;
                        break;
                    default:
                        $url = self::FAILURE_URL;
                }

                $this->antomLogger->error(
                    __('Inquiry paymentStatus failed for order ' . $order->getIncrementId())
                );
            } elseif ($inquiryResult == AntomConstants::SUCCESS) {
                $this->antomLogger->addAntomInfoLog(
                    sprintf(
                        'Session Info - LastRealOrderId: %s, LastQuoteId: %s',
                        $this->session->getLastRealOrderId(),
                        $this->session->getLastQuoteId()
                    )
                );

                try {
                    // When the payment is success, inactive quote and redirect to the success page
                    $quote = $this->quoteRepository->get($order->getQuoteId());
                    $quote->setIsActive(false);
                    $this->quoteRepository->save($quote);
                    $this->antomLogger->addAntomInfoLog('Quote deactivated successfully for order: ' . $order->getIncrementId());

                    // Restore checkout session data so success page works
                    $this->session->setLastQuoteId($quote->getId())
                        ->setLastSuccessQuoteId($quote->getId())
                        ->setLastRealOrderId($order->getIncrementId())
                        ->setLastOrderId($order->getId())
                        ->setLastOrderStatus($order->getStatus());

                    $this->antomLogger->addAntomInfoLog('Setting success session for order: ' . $order->getIncrementId());
                    $url = self::SUCCESS_URL;
                } catch (\Exception $e) {
                    $this->antomLogger->error('Error processing success: ' . $e->getMessage());
                    $url = self::FAILURE_URL;
                }
            } else {
                $url = self::FAILURE_URL;
            }
        } elseif (strcmp($paymentStatus, AntomConstants::FAIL) == 0) {
            // the payment has failed, redirect to fail page
            $url = self::FAILURE_URL;
        } elseif (strcmp($paymentStatus, AntomConstants::SUCCESS) == 0) {
            // the payment has succeeded, redirect to success page
            $url = self::SUCCESS_URL;
        } else {
            // unknown payment status, redirect
            $url = self::FAILURE_URL;
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($url);
        return $resultRedirect;
    }

    /**
     * Polling payment status
     *
     * @param $storeId
     * @param $paymentId
     * @param $paymentRequestId
     * @return string
     */
    private function inquiryPaymentStatus($storeId, $paymentId, $paymentRequestId)
    {
        $times = 3;
        $cnt = 0;
        while ($cnt < $times) {
            $paymentStatus = $this->paymentStatusHelper->getPaymentStatus($storeId, $paymentId, $paymentRequestId);
            if ($paymentStatus == AntomConstants::UNKNOWN) {
                sleep(1 << $cnt);
                $cnt++;
            } else {
                return $paymentStatus;
            }
        }
        return AntomConstants::UNKNOWN;
    }
}
