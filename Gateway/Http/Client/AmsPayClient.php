<?php

namespace Antom\Core\Gateway\Http\Client;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Client\DefaultAlipayClient;
use Model\BrowserInfo;
use Model\Buyer;
use Model\PaymentFactor;
use Request\pay\AlipayPayRequest;
use Model\Amount;
use Model\Env;
use Model\Order;
use Model\PaymentMethod;
use Model\SettlementStrategy;


/**
 * Build Antom (Alipay) request, send request to Antom gateway and receive Antom response
 */
class AmsPayClient implements ClientInterface
{

    /**
     * Send the payment request via Antom sdk
     * @param TransferInterface $transferObject
     * @return array|mixed
     * @throws \Exception
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $clientConf = $transferObject->getClientConfig();
        $client = $this->createAntomClient($clientConf);
        $body = $transferObject->getBody();
        $uri = $transferObject->getUri();
        $alipayRequest = $this->buildAlipayRequest($body);
        $alipayRequest->setPath($uri);
        $alipayResponse = $client->execute($alipayRequest);
        return json_decode(json_encode($alipayResponse), true);
    }

    /**
     * Build alipayPayRequest
     * @param array $body
     * @return AlipayPayRequest
     */
    private function buildAlipayRequest(array $body): AlipayPayRequest
    {
        $request = new AlipayPayRequest();
        // set up env
        $env = new Env();
        $magentoEnv = $body[AntomConstants::ENV];
        if (!empty($magentoEnv[AntomConstants::CLIENT_IP])) {
            $env->setClientIp($magentoEnv[AntomConstants::CLIENT_IP]);
        }
        $browserInfo = new BrowserInfo();
        if (!empty($magentoEnv[AntomConstants::LANGUAGE])) {
            $browserInfo->setLanguage($magentoEnv[AntomConstants::LANGUAGE]);
        }
        if (!empty($magentoEnv['acceptHeader'])) {
            $browserInfo->setAcceptHeader($magentoEnv['acceptHeader']);
        }
        $env->setBrowserInfo($browserInfo);
        $env->setTerminalType($magentoEnv[AntomConstants::TERMINAL_TYPE]);
        $request->setEnv($env);

        // set up paymentAmount
        $paymentAmount = new Amount();
        $paymentAmount->setCurrency($body[AntomConstants::PAYMENT_AMOUNT][AntomConstants::CURRENCY]);
        $paymentAmount->setValue($body[AntomConstants::PAYMENT_AMOUNT][AntomConstants::VALUE]);
        $request->setPaymentAmount($paymentAmount);

        // set up paymentMethod
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setPaymentMethodType(
            $body[AntomConstants::PAYMENT_METHOD][AntomConstants::PAYMENT_METHOD_TYPE]
        );
        if (isset($body[AntomConstants::PAYMENT_METHOD][AntomConstants::PAYMENT_METHOD_ID])) {
            $paymentMethod->setPaymentMethodId($body[AntomConstants::PAYMENT_METHOD][AntomConstants::PAYMENT_METHOD_ID]);
        }
        $request->setPaymentMethod($paymentMethod);

        // set up paymentFactor
        if (!empty($body[AntomConstants::PAYMENT_FACTOR])) {
            $paymentFactor = new PaymentFactor();
            $paymentFactor->setIsAuthorization($body[AntomConstants::PAYMENT_FACTOR][AntomConstants::IS_AUTHORIZATION]);
            $request->setPaymentFactor($paymentFactor);
        }


        // set up paymentRequestId
        $request->setPaymentRequestId($body[AntomConstants::PAYMENT_REQUEST_ID]);

        // set up payNotifyUrl
        if (!empty($body[AntomConstants::PAYMENT_NOTIFY_URL])) {
            $request->setPaymentNotifyUrl($body[AntomConstants::PAYMENT_NOTIFY_URL]);
        }

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
        $buyer = new Buyer();
        $buyer->setReferenceBuyerId($body[AntomConstants::ORDER][AntomConstants::REFERENCE_BUYER_ID]);
        $order->setBuyer($buyer);

        $order->setOrderAmount($orderAmount);
        $request->setOrder($order);
        // set up productCode
        $request->setProductCode($body[AntomConstants::PRODUCT_CODE]);

        // set up redirectUrl
        $request->setPaymentRedirectUrl($body[AntomConstants::PAYMENT_REDIRECT_URL]);

        // set up settlementStrategy
        $settlementStrategy = new SettlementStrategy();
        $settlementStrategy->setSettlementCurrency(
            $body[AntomConstants::SETTLEMENT_STRATEGY][AntomConstants::SETTLEMENT_CURRENCY]
        );
        $request->setSettlementStrategy($settlementStrategy);

        // set up extendInfo
        $extendInfo = [
            'ISO_VENDOR' => 'Magento'
        ];
        $request->setExtendInfo($extendInfo);

        return $request;
    }

    /**
     * Build Antom client for the API query
     * @param $clientConf
     * @return DefaultAlipayClient
     */
    private function createAntomClient(array $clientConf): DefaultAlipayClient
    {
        $gatewayUrl = $clientConf[AntomConstants::GATEWAY_URL];
        $merchantPrivateKey = $clientConf[AntomConstants::MERCHANT_PRIVATE_KEY];
        $antomPublicKey = $clientConf[AntomConstants::ANTOM_PUBLIC_KEY];
        $clientId = $clientConf[AntomConstants::CLIENT_ID];
        $client = new DefaultAlipayClient($gatewayUrl, $merchantPrivateKey, $antomPublicKey, $clientId);
        return $client;
    }
}
