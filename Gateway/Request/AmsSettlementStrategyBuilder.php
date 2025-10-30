<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsSettlementStrategyBuilder implements BuilderInterface
{
    /**
     * Build settlement strategy for antom payment
     * @param array $buildSubject
     * @return array<string, array<string, string>>
     */
    public function build(array $buildSubject): array
    {
        $settlementStrategy = [
            AntomConstants::SETTLEMENT_CURRENCY => 'USD'
        ];
        return [
            AntomConstants::SETTLEMENT_STRATEGY => $settlementStrategy
        ];
    }
}
