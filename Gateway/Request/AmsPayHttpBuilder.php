<?php

namespace Antom\Core\Gateway\Request;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AmsPayHttpBuilder implements BuilderInterface
{
    private const PAYMENT_METHOD_URI_MAP = [
        AntomConstants::MAGENTO_ALIPAY_CN => AntomConstants::AMS_PAY_URI,
        AntomConstants::MAGENTO_ANTOM_CARD => AntomConstants::UPDATE_SESSION_URI
    ];
    /**
     * Field config, used to read admin page configurations
     * @var AntomConfig
     */
    private AntomConfig $config;

    /**
     * AmsPayHttpBuilder constructor
     * @param AntomConfig $config
     */
    public function __construct(AntomConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Build the http method and uri
     * @param array $buildSubject
     * @return array|string[]
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $paymentMethod = $payment->getMethod();

        $order = $paymentDataObject->getOrder();
        $storeId = $order->getStoreId();
        $antomGatewayUrl = $this->config->getAntomGatewayUrl($storeId);
        $clientConfig = [
            AntomConstants::CLIENT_ID => $this->config->getAntomClientId($storeId),
            AntomConstants::MERCHANT_PRIVATE_KEY => $this->config->getMerchantPrivateKey($storeId),
            AntomConstants::ANTOM_PUBLIC_KEY => $this->config->getAntomPublicKey($storeId),
            AntomConstants::GATEWAY_URL => $antomGatewayUrl,
        ];
        return [
            AntomConstants::METHOD => AntomConstants::POST,
            AntomConstants::URI => self::PAYMENT_METHOD_URI_MAP[$paymentMethod],
            AntomConstants::CLIENT_CONFIG => $clientConfig
        ];
    }
}
