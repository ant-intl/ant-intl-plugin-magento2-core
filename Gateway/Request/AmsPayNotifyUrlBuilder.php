<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Antom\Core\Helper\RequestHelper;

class AmsPayNotifyUrlBuilder implements BuilderInterface
{
    const CONTROLLER_PATH = '/antom/notification';
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
        $notifyUrl = $domain . self::CONTROLLER_PATH;
        return [
            AntomConstants::PAYMENT_NOTIFY_URL => $notifyUrl
        ];
    }
}
