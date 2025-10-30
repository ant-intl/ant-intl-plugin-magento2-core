<?php

namespace Antom\Core\Helper;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Request\PaymentInquiryRequest;
use Client\DefaultAlipayClient;

class PaymentStatusHelper
{
    const PAYMENT_INQUIRY_PATH = '/ams/api/v1/payments/inquiryPayment';
    private $antomConfig;
    private $antomLogger;

    public function __construct(AntomConfig $antomConfig, AntomLogger $antomLogger)
    {
        $this->antomConfig = $antomConfig;
        $this->antomLogger = $antomLogger;
    }

    /**
     * Query payment status by calling inquiryPayment API
     * @param string $storeId
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $storeId, string $paymentId): string
    {
        $client = $this->createAntomClient($storeId);
        $url = self::PAYMENT_INQUIRY_PATH;
        $paymentInquiryRequest = new PaymentInquiryRequest();
        $paymentInquiryRequest->setPaymentId($paymentId);
        $paymentInquiryRequest->setPath($url);
        $response = null;
        try {
            $response = $client->execute($paymentInquiryRequest);
        } catch (\Exception $e) {
            $msg = sprintf("Error when inquirying payment: %s", $e->getMessage());
            $this->antomLogger->error(__($msg));
        }
        $response = json_decode(json_encode($response), true);
        if (!empty($response)
            && !empty($response[AntomConstants::RESULT])
            && strcmp($response[AntomConstants::RESULT][AntomConstants::RESULT_STATUS], AntomConstants::S) == 0) {
            return $response[AntomConstants::PAYMENT_STATUS];
        }
        return AntomConstants::UNKNOWN;
    }

    /**
     * @param string $storeId
     * @return DefaultAlipayClient
     */
    private function createAntomClient(string $storeId)
    {
        $gatewayUrl = $this->antomConfig->getAntomGatewayUrl($storeId);
        $merchantPrivateKey = $this->antomConfig->getMerchantPrivateKey($storeId);
        $antomPublicKey = $this->antomConfig->getAntomPublicKey($storeId);
        $clientId = $this->antomConfig->getAntomClientId($storeId);
        return new DefaultAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId);
    }
}
