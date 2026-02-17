<?php

namespace Antom\Core\Api;

interface GuestAntomRestoreQuoteInterface
{
    /**
     * Restore quote by order id for guest or logged-in user
     *
     * @param string $orderId
     * @param string|null $cartId
     * @return array
     */
    public function restoreQuoteByOrderId($orderId, $cartId = null);
}
