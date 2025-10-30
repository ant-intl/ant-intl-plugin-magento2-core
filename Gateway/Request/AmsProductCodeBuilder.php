<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsProductCodeBuilder implements BuilderInterface
{
    /**
     * Build productCode for Antom AMS pay
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        return [
            AntomConstants::PRODUCT_CODE => AntomConstants::CASHIER_PAYMENT
        ];
    }

}
