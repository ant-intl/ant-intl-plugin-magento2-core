<?php

namespace Antom\Core\Api;

/**
 * Interface for querying the Antom order payment status for guest shoppers
 */
interface GuestAntomCreatePaymentSessionInterface
{
    /**
     * Get the payment status for guest user, used for redirect payment methods
     * @param string $orderId
     * @param string $cartId
     * @return string
     */
    public function createPaymentSession(string $cartId, string $email);
}
