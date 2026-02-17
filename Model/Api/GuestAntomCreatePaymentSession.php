<?php

namespace Antom\Core\Model\Api;


use Antom\Core\Api\GuestAntomCreatePaymentSessionInterface;

class GuestAntomCreatePaymentSession extends AbstractPaymentSession implements GuestAntomCreatePaymentSessionInterface
{
    public function createPaymentSession(string $cartId, string $email)
    {
        return $this::createPaymentSessionHelper($cartId, $email);
    }
}
