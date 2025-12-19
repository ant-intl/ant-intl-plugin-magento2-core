<?php
namespace Antom\Core\Observer;

use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Antom\Core\Logger\AntomLogger;

class PaymentAssignDataObserver implements ObserverInterface
{
    /**
     * @var PaymentCollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param AntomLogger $logger
     */
    public function __construct(
        PaymentCollectionFactory $paymentCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        AntomLogger $logger
    ) {
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Execute method when observer is triggered
     *
     * @param Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer) : void
    {
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $observer->getEvent()->getPaymentModel();

        /** @var \Magento\Framework\DataObject $data */
        $data = $observer->getEvent()->getData('data');

        if ($payment->getMethodInstance()->getCode() !== 'antom_card') {
            return;
        }

        $additionalData = $data->getAdditionalData();

        if (is_array($additionalData) && isset($additionalData[AntomConstants::PAYMENT_REQUEST_ID])) {
            $paymentRequestId = $additionalData[AntomConstants::PAYMENT_REQUEST_ID];

            $this->logger->addAntomInfoLog('=== Processing Antom payment assignment ===', [
                'payment_request_id' => $paymentRequestId
            ]);

            // 检查是否存在已成功的订单
            $this->validatePaymentRequest($paymentRequestId);

            // 如果验证通过，设置支付数据
            $payment->setData(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);

            $this->logger->addAntomInfoLog('Payment request validated and assigned', [
                'payment_request_id' => $paymentRequestId
            ]);
        }
    }

    /**
     * 验证支付请求 - 检查是否存在已成功的订单
     *
     * @param string $paymentRequestId
     * @return void
     * @throws LocalizedException
     */
    private function validatePaymentRequest(string $paymentRequestId)
    {
        $existingOrders = $this->findExistingOrders($paymentRequestId);

        foreach ($existingOrders as $orderData) {
            $order = $orderData['order'];
            $orderStatus = $order->getState();

            $this->logger->addAntomInfoLog('Found existing order for payment request', [
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'current_state' => $orderStatus,
                'current_status' => $order->getStatus(),
                'payment_request_id' => $paymentRequestId
            ]);

            // 检查订单状态
            if ($this->isOrderSuccessful($order)) {
                // 订单已成功，拒绝支付
                $this->rejectPayment($order, $paymentRequestId);
            } elseif ($this->canReplaceOrder($order)) {
                // 订单可以替换，关闭旧订单
                $this->closeReplaceableOrder($order, $paymentRequestId);
            }
        }
    }

    /**
     * 检查订单是否已经成功（不允许重复支付）
     *
     * @param Order $order
     * @return bool
     */
    private function isOrderSuccessful(Order $order)
    {
        $successfulStates = [
            Order::STATE_PROCESSING,  // 处理中
            Order::STATE_COMPLETE,    // 已完成
            Order::STATE_CLOSED       // 已关闭
        ];

        return in_array($order->getState(), $successfulStates);
    }

    /**
     * 检查订单是否可以被替换
     *
     * @param Order $order
     * @return bool
     */
    private function canReplaceOrder(Order $order)
    {
        $replaceableStates = [
            Order::STATE_NEW,             // 新订单
            Order::STATE_PENDING_PAYMENT, // 待支付
            Order::STATE_CANCELED         // 已取消（可以重新创建）
        ];

        return in_array($order->getState(), $replaceableStates);
    }

    /**
     * 拒绝支付 - 抛出异常
     *
     * @param Order $order
     * @param string $paymentRequestId
     * @throws LocalizedException
     */
    private function rejectPayment(Order $order, string $paymentRequestId)
    {
        $errorMessage = sprintf(
            'Payment rejected: Order #%s with payment_request_id %s is already %s. Duplicate payment not allowed.',
            $order->getIncrementId(),
            $paymentRequestId,
            $order->getStatus()
        );

        $this->logger->addAntomWarning('Payment rejected due to existing successful order', [
            'order_id' => $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'order_state' => $order->getState(),
            'order_status' => $order->getStatus(),
            'payment_request_id' => $paymentRequestId,
            'rejection_reason' => 'duplicate_successful_payment'
        ]);

        // 抛出异常，阻止支付继续进行
        throw new LocalizedException(__($errorMessage));
    }

    /**
     * 关闭可替换的订单
     *
     * @param Order $order 需要关闭的订单对象
     * @param string $paymentRequestId 支付请求ID
     * @return void
     * @throws \Exception 当订单关闭失败时抛出异常
     */
    private function closeReplaceableOrder(Order $order, string $paymentRequestId): void
    {
        try {
            $comment = sprintf(
                'Order replaced due to new payment attempt with same payment_request_id: %s',
                $paymentRequestId
            );

            if ($order->getState() !== Order::STATE_CANCELED) {
                if ($order->canCancel()) {
                    $order->cancel(); // 触发系统取消逻辑，库存/支付回退逻辑
                }
                $order->setStatus(AntomConstants::ANTOM_ELEMENT_CARD_FAILED)
                    ->setState(Order::STATE_CANCELED)
                    ->addCommentToStatusHistory($comment, AntomConstants::ANTOM_ELEMENT_CARD_FAILED);
                $this->orderRepository->save($order);
            }

            $this->logger->addAntomInfoLog('Replaceable order closed successfully', [
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'payment_request_id' => $paymentRequestId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to close replaceable order', [
                'order_id' => $order->getId(),
                'payment_request_id' => $paymentRequestId,
                'error' => $e->getMessage()
            ]);

            // 即使关闭失败，也不阻止新支付（记录警告）
            $this->logger->addAntomWarning('Continuing with new payment despite closure failure');
        }
    }

    /**
     * 查找现有订单
     *
     * @param string $paymentRequestId
     * @return array
     */
    private function findExistingOrders(string $paymentRequestId) : array
    {
        $paymentCollection = $this->paymentCollectionFactory->create();
        $paymentCollection->addFieldToFilter(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $paymentRequestId);
        $paymentCollection->addFieldToFilter(AntomConstants::METHOD, AntomConstants::MAGENTO_ANTOM_CARD);

        $existingOrders = [];

        foreach ($paymentCollection as $payment) {
            $order = $payment->getOrder();
            if ($order && $order->getId()) {
                $existingOrders[] = [
                    'order' => $order,
                    'payment' => $payment
                ];
            }
        }

        return $existingOrders;
    }
}
