<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Api\AntomRestoreQuoteInterface;

class AntomRestoreQuote extends AbstractAntomRestoreQuote implements AntomRestoreQuoteInterface
{
    public function restoreQuoteByOrderId($orderId, $cartId=null) {
        return $this::restoreQuoteByOrderIdHelper($orderId, $cartId);
    }
}
