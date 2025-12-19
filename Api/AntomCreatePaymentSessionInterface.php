<?php

namespace Antom\Core\Api;

/**
 * Interface for querying the Antom order payment status
 */
interface AntomCreatePaymentSessionInterface
{
    /**
     * @param string $cartId
     * @return mixed
     */
    public function createPaymentSession(string $cartId);
}
