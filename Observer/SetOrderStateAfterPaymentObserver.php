<?php

namespace Antom\Core\Observer;

use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;

class SetOrderStateAfterPaymentObserver implements ObserverInterface
{
    /**
     * @var StatusResolver
     */
    private $statusResolver;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * SetOrderStateAfterPaymentObserver constructor
     * @param StatusResolver $statusResolver
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        StatusResolver           $statusResolver,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->statusResolver = $statusResolver;
        $this->orderRepository = $orderRepository;
    }

    public function execute(Observer $observer)
    {
        if ($observer->getData('payment') == null
            || $observer->getData('payment')->getOrder() == null
            || $observer->getData('payment')->getAdditionalInformation(AntomConstants::PAYMENT_STATUS) == null) {
            return;
        }

        $payment = $observer->getData('payment');
        $order = $payment->getOrder();
        $paymentStatus = $payment->getAdditionalInformation(AntomConstants::PAYMENT_STATUS);

        if (strcmp($paymentStatus, AntomConstants::INITIATED) == 0) {
            $status = $this->statusResolver->getOrderStatusByState(
                $payment->getOrder(),
                Order::STATE_PENDING_PAYMENT
            );
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus($status);
            $message = __($payment->getAdditionalInformation(AntomConstants::RESULT_MESSAGE));
            $order->addCommentToStatusHistory($message, $status);
            $this->orderRepository->save($order);
        }
    }
}
