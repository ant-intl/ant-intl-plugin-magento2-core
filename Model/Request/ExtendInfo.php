<?php

namespace Antom\Core\Model\Request;

class ExtendInfo {
    public $PaymentSource;
    public $supportedCardBrands;
    public function __construct($PaymentSource, $supportedCardBrands) {
        $this->PaymentSource = $PaymentSource;
        $this->supportedCardBrands = $supportedCardBrands;
    }
}
