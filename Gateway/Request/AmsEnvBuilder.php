<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Antom\Core\Helper\RequestHelper;

class AmsEnvBuilder implements BuilderInterface
{
    /**
     * Util to build request
     * @var RequestHelper
     */
    private RequestHelper $helper;

    /**
     * AmsEnvBuilder constructor
     * @param RequestHelper $requestHelper
     */
    public function __construct(
        RequestHelper $requestHelper
    )
    {
        $this->helper = $requestHelper;
    }

    /**
     * Build env info for Antom ams payment
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $env = $this->helper->composeEnvInfo();
        return [
            AntomConstants::ENV => $env
        ];
    }

}
