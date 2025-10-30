<?php

namespace Antom\Core\Api;

/**
 * Interface for querying the Antom order payment status
 */
interface AntomOrderPaymentStatusInterface
{
    /**
     * Get the payment status for login user, used for redirect payment methods
     * @param string $orderId
     * @return array
     */
    public function getOrderPaymentStatus(string $orderId): string;
}
