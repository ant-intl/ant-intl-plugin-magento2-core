<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Api\GuestAntomOrderPaymentStatusInterface;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class GuestAntomOrderPaymentStatus implements GuestAntomOrderPaymentStatusInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var AntomLogger
     */
    private $antomLogger;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * GuestAntomOrderPaymentStatus constructor
     * @param OrderRepositoryInterface $orderRepository
     * @param AntomLogger $antomLogger
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        OrderRepositoryInterface        $orderRepository,
        AntomLogger                     $antomLogger,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    )
    {
        $this->orderRepository = $orderRepository;
        $this->antomLogger = $antomLogger;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }


    public function getOrderPaymentStatus(string $orderId, string $cartId): string
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (NoSuchEntityException $e) {
            $errorMessage = sprintf("Quote with masked ID %s not found!", $cartId);
            $this->antomLogger->error($errorMessage);

            throw new NotFoundException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $order = $this->orderRepository->get($orderId);

        if (intval($order->getQuoteId()) !== $quoteId) {
            $errorMessage = sprintf("Order for ID %s not found!", $orderId);
            $this->antomLogger->error($errorMessage);

            throw new NotFoundException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $payment = $order->getPayment();
        $referenceOrderId = $order->getIncrementId();

        $additionalInformation = $payment->getAdditionalInformation();

        if ($additionalInformation == null || $additionalInformation[AntomConstants::PAYMENT_STATUS] == null) {
            $this->antomLogger->error("PaymentStatus not exist, mark it as fail");
            $paymentStatus = AntomConstants::FAIL;
        } else {
            $paymentStatus = $additionalInformation[AntomConstants::PAYMENT_STATUS];
        }
        $is3ds = strtolower($paymentStatus) === 'initiated' ? true : false;
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
                    "message" => $warningMessage,
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
            $paymentAction = $additionalInformation[AntomConstants::PAYMENT_ACTION];
            $result = [
                AntomConstants::PAYMENT_STATUS => $additionalInformation[AntomConstants::PAYMENT_STATUS],
                AntomConstants::PAYMENT_ACTION => json_decode($paymentAction, true),
                "message" => AntomConstants::SUCCESS,
                AntomConstants::REFERENCE_ORDER_ID => $referenceOrderId,
                AntomConstants::IS_3DS => $is3ds
            ];
            return json_encode($result);
        }


        return json_encode([
            AntomConstants::PAYMENT_STATUS => "error",
            "message" => "error"
        ]);
    }

}
