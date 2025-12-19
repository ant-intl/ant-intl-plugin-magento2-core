<?php

namespace Antom\Core\Model\Api;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class AbstractAntomRestoreQuote
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;
    /**
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param UserContextInterface $userContext
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        UserContextInterface $userContext,
        CheckoutSession $checkoutSession
    ) {
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->userContext = $userContext;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Restore Quote
     *
     * @param string $orderId
     * @param string $cartId
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function restoreQuoteByOrderIdHelper($orderId, $cartId = null)
    {
        // 1. 获取订单
        $order = $this->orderRepository->get($orderId);
        if (!$order->getEntityId()) {
            throw new LocalizedException(__('Order not found.'));
        }

        // 2. 验证订单状态（例如仅允许未支付、已取消订单恢复）
        $notRestoreStates = ['complete', 'processing'];
        if (in_array($order->getState(), $notRestoreStates, true)) {
            throw new LocalizedException(__('Order state "%1" cannot be restored.', $order->getState()));
        }

        $orderQuoteId = (int)$order->getQuoteId();

        // 3. guest 或 登录用户权限 & 关联验证
        if ($order->getCustomerIsGuest()) {
            if (empty($cartId)) {
                throw new LocalizedException(__('masked_quote_id is required for guest.'));
            }
            $quoteMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            if ((int)$quoteMask->getQuoteId() !== $orderQuoteId) {
                throw new LocalizedException(__('Quote ID mismatch for guest restore.'));
            }
        } else {
            $customerId = $this->userContext->getUserId();
            if ($customerId !== (int)$order->getCustomerId()) {
                throw new LocalizedException(__('This order does not belong to the current customer.'));
            }
        }

        // 4. 获取并恢复 quote
        $quote = $this->quoteRepository->get($orderQuoteId);
        $quote->setIsActive(true);
        $quote->setReservedOrderId(null);
        $this->quoteRepository->save($quote);

        // 5. 设置到 checkout session
        try {
            $this->checkoutSession->setQuoteId($quote->getId());
            $this->checkoutSession->replaceQuote($quote);
        } catch (\Exception $e) {
            // session 异常不影响接口功能
        }

        return [
            'success' => true,
            'message' => 'Quote restored successfully',
            'order_id' => $orderId,
            'increment_id' => $order->getIncrementId(),
            'quote_id' => $quote->getId(),
            'items_count' => $quote->getItemsCount(),
            'grand_total' => $quote->getGrandTotal()
        ];
    }
}
