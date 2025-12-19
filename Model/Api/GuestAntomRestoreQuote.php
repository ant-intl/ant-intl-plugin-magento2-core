<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Api\GuestAntomRestoreQuoteInterface;

class GuestAntomRestoreQuote extends AbstractAntomRestoreQuote implements GuestAntomRestoreQuoteInterface
{
    public function restoreQuoteByOrderId($orderId, $cartId=null) {
        return $this::restoreQuoteByOrderIdHelper($orderId, $cartId);
    }
}
