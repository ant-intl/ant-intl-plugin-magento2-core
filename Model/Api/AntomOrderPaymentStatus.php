<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Api\AntomOrderPaymentStatusInterface;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

class AntomOrderPaymentStatus implements AntomOrderPaymentStatusInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var AntomLogger
     */
    protected AntomLogger $antomLogger;

    /**
     * AntomOrderPaymentStatus constructor
     * @param OrderRepositoryInterface $orderRepository
     * @param AntomLogger $antomLogger
     */
    public function __construct(OrderRepositoryInterface $orderRepository, AntomLogger $antomLogger)
    {
        $this->orderRepository = $orderRepository;
        $this->antomLogger = $antomLogger;
    }

    public function getOrderPaymentStatus(string $orderId): string
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $exception) {
            $errorMessage = sprintf("Order for ID %s not found!", $orderId);
            $this->antomLogger->error($errorMessage);
            throw $exception;
        }

        $referenceOrderId = $order->getIncrementId();

        $payment = $order->getPayment();

        $additionalInformation = $payment->getAdditionalInformation();
        $paymentMethod = $payment->getMethod();
        if (strcmp($paymentMethod, AntomConstants::MAGENTO_ALIPAY_CN) == 0) {
            if (strcmp($paymentMethod, $additionalInformation[AntomConstants::PAYMENT_METHOD]) != 0) {
                $errorMessage = sprintf("Payment method %s not found!", $paymentMethod);
                $this->antomLogger->error($errorMessage);
                throw new LocalizedException(__('Payment method not matched!'));
            }
            if (empty($additionalInformation[AntomConstants::PAYMENT_STATUS]) ||
                strcmp($additionalInformation[AntomConstants::PAYMENT_STATUS], AntomConstants::FAIL) == 0) {
                $warningMessage = sprintf(
                    "Payment status [%s] not valid for redirect!",
                    $additionalInformation[AntomConstants::PAYMENT_STATUS]
                );
                $this->antomLogger->addAntomWarning($warningMessage);
                return json_encode([
                    AntomConstants::PAYMENT_STATUS => AntomConstants::FAIL,
                    "message" => $warningMessage
                ]);
            }
            $paymentAction = $additionalInformation[AntomConstants::PAYMENT_ACTION];
            $result = [
                AntomConstants::PAYMENT_STATUS => $additionalInformation[AntomConstants::PAYMENT_STATUS],
                AntomConstants::PAYMENT_ACTION => json_decode($paymentAction, true),
                "message" => AntomConstants::SUCCESS
            ];
            return json_encode($result);
        }

        if (strcmp($paymentMethod, AntomConstants::MAGENTO_ANTOM_CARD) == 0) {
            if (strcmp($paymentMethod, $additionalInformation[AntomConstants::PAYMENT_METHOD]) != 0) {
                $errorMessage = sprintf("Payment method %s not found!", $paymentMethod);
                $this->antomLogger->error($errorMessage);
            }
            if (empty($additionalInformation[AntomConstants::PAYMENT_STATUS]) ||
                strcmp($additionalInformation[AntomConstants::PAYMENT_STATUS], AntomConstants::FAIL) == 0) {
                $warningMessage = sprintf(
                    "Payment status [%s] not valid for redirect!",
                    $additionalInformation[AntomConstants::PAYMENT_STATUS]
                );
                $this->antomLogger->addAntomWarning($warningMessage);
                return json_encode([
                    AntomConstants::PAYMENT_STATUS => AntomConstants::FAIL,
                    "message" => $warningMessage
                ]);
            }

            $paymentStatus = $additionalInformation[AntomConstants::PAYMENT_STATUS];

            $result = [
                AntomConstants::PAYMENT_METHOD => $paymentMethod,
                AntomConstants::PAYMENT_STATUS => $paymentStatus,
                "message" => AntomConstants::SUCCESS,
                AntomConstants::REFERENCE_ORDER_ID => $referenceOrderId
            ];
            return json_encode($result);
        }

        return json_encode([
            AntomConstants::PAYMENT_STATUS => "error",
            "message" => "error"
        ]);
    }
}
