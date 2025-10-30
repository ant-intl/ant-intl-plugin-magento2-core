<?php

namespace Antom\Core\Gateway\Request\Header;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsHeaderBuilder implements BuilderInterface
{
    /**
     * Field config, used to read admin page configurations
     * @var \Antom\Core\Config\AntomConfig
     */
    private \Antom\Core\Config\AntomConfig $config;

    /**
     * AmsHeaderBuilder constructor
     * @param \Antom\Core\Config\AntomConfig $config
     */
    public function __construct(\Antom\Core\Config\AntomConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate Antom request headers
     *
     * @param array $buildSubject expects ['storeId' => int]
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $storeId = $order->getStoreId();
        $headers = [
            AntomConstants::CLIENT_ID => $this->config->getAntomClientId($storeId),
            AntomConstants::CONTENT_TYPE => 'application/json; charset=UTF-8',
            AntomConstants::REQUEST_TIME => (string)(int)(microtime(true) * 1000),
        ];

        return [AntomConstants::HEADERS => $headers];
    }
}
