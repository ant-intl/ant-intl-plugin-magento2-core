<?php

namespace Antom\Core\Gateway\Http\Client;

use Antom\Core\Client\LiveAlipayClient;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Model\Request\AlipayUpdatePaymentSessionRequest;
use Antom\Core\Model\Request\ExtendInfo;
use Client\DefaultAlipayClient;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Model\Amount;
use Model\Order;
use Request\pay\AlipayPayRequest;

/**
 * Build Antom (Alipay) request, send request to Antom gateway and receive Antom response
 */
class AmsPayLiveClient implements ClientInterface
{

    /**
     * @var AntomLogger
     */
    private $antomLogger;

    public function __construct(AntomLogger $antomLogger) {
        $this->antomLogger = $antomLogger;
    }

    /**
     * Send the payment request via Antom sdk
     * @param TransferInterface $transferObject
     * @return array|mixed
     * @throws \Exception
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $clientConf = $transferObject->getClientConfig();
        $client = $this->createAntomLiveClient($clientConf);
        $body = $transferObject->getBody();
        $uri = $transferObject->getUri();
        $alipayRequest = $this->buildAlipayRequest($body);
        $alipayRequest->setPath($uri);
        $this->antomLogger->addAntomInfoLog("Starting to send updatePaymentSession request",
            json_decode(json_encode($alipayRequest), true));
        $alipayResponse = $client->execute($alipayRequest);
        $alipayResponseDecoded = json_decode(json_encode($alipayResponse), true);
        $this->antomLogger->addAntomInfoLog("UpdatePaymentSession response: ", $alipayResponseDecoded);
        return json_decode(json_encode($alipayResponse), true);
    }

    /**
     * Build alipayPayRequest
     * @param array $body
     * @return AlipayPayRequest
     */
    private function buildAlipayRequest(array $body): AlipayUpdatePaymentSessionRequest
    {
        $request = new AlipayUpdatePaymentSessionRequest();
        // set up paymentAmount
        $paymentAmount = new Amount();
        $paymentAmount->setCurrency($body[AntomConstants::PAYMENT_AMOUNT][AntomConstants::CURRENCY]);
        $paymentAmount->setValue($body[AntomConstants::PAYMENT_AMOUNT][AntomConstants::VALUE]);
        $request->setPaymentAmount($paymentAmount);

        // set up paymentRequestId
        $request->setPaymentRequestId($body[AntomConstants::PAYMENT_REQUEST_ID]);

        // set up order
        $order = new Order();
        $order->setReferenceOrderId($body[AntomConstants::ORDER][AntomConstants::REFERENCE_ORDER_ID]);
        $order->setOrderDescription($body[AntomConstants::ORDER][AntomConstants::ORDER_DESCRIPTION]);
        $orderAmount = new Amount();
        $orderAmount->setCurrency(
            $body[AntomConstants::ORDER][AntomConstants::ORDER_AMOUNT][AntomConstants::CURRENCY]
        );
        $orderAmount->setValue(
            $body[AntomConstants::ORDER][AntomConstants::ORDER_AMOUNT][AntomConstants::VALUE]
        );
        $order->setOrderAmount($orderAmount);
        $request->setOrder($order);

        // set up extendInfo
        // supportCardBrands need to be null, empty array is not working
        $extendInfo = new ExtendInfo("MAGENTO", null);
        $request->setExtendInfo($extendInfo);
        $request->setConfirm(true);

        return $request;
    }

    /**
     * Build Antom client for the API query
     * @param $clientConf
     * @return DefaultAlipayClient
     */
    private function createAntomLiveClient(array $clientConf): DefaultAlipayClient
    {
        $gatewayUrl = $clientConf[AntomConstants::GATEWAY_URL];
        $merchantPrivateKey = $clientConf[AntomConstants::MERCHANT_PRIVATE_KEY];
        $antomPublicKey = $clientConf[AntomConstants::ANTOM_PUBLIC_KEY];
        $clientId = $clientConf[AntomConstants::CLIENT_ID];
        $client = new LiveAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId);
        return $client;
    }
}
