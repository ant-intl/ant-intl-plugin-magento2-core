<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Gateway\AntomPathConstants;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Antom\Core\Helper\RequestHelper;

class AmsPayNotifyUrlBuilder implements BuilderInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * AmsPayNotifyUrlBuilder constructor
     * @param $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }


    /**
     * Build the paymentNotifyUrl
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $domain = $this->requestHelper->getDomain();
        $notifyUrl = $domain . AntomPathConstants::CONTROLLER_PATH;
        return [
            AntomConstants::PAYMENT_NOTIFY_URL => $notifyUrl
        ];
    }
}
