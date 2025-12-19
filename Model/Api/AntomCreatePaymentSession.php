<?php

namespace Antom\Core\Model\Api;

use Antom\Core\Api\AntomCreatePaymentSessionInterface;

class AntomCreatePaymentSession extends AbstractPaymentSession implements AntomCreatePaymentSessionInterface
{
    function createPaymentSession(string $cartId)
    {
       return $this::createPaymentSessionHelper($cartId, $this->userContext->getUserId());
    }
}
