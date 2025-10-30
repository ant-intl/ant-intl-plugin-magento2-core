<?php

namespace Antom\Core\Plugin;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Model\Method\Adapter;

class PaymentMethodIsActivePlugin
{
    private $antomConfig;

    /**
     * @param $antomConfig
     */
    public function __construct(AntomConfig $antomConfig)
    {
        $this->antomConfig = $antomConfig;
    }


    public function afterIsActive(Adapter $subject, $result)
    {
        if ($subject->getCode() == AntomConstants::MAGENTO_ANTOM_CARD && $result) {
            $enabledCardBrands = $this->antomConfig->getEnabledCards($subject->getStore());
            return !empty($enabledCardBrands);
        }
        return $result;
    }
}
