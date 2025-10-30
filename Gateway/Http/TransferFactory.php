<?php

namespace Antom\Core\Gateway\Http;

use Antom\Core\Gateway\AntomConstants;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Antom\Core\Helper\AssertHelper;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * TransferFactory constructor
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder
    )
    {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Create TransferO
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request): TransferInterface
    {
        $this->validateParams($request);
        $this->transferBuilder->setHeaders($request[AntomConstants::HEADERS]);
        $this->transferBuilder->setMethod($request[AntomConstants::METHOD]);
        $this->transferBuilder->setUri($request[AntomConstants::URI]);
        $this->transferBuilder->setClientConfig($request[AntomConstants::CLIENT_CONFIG]);
        // set up required parameters
        $body = [
            AntomConstants::ENV => $request[AntomConstants::ENV],
            AntomConstants::PAYMENT_AMOUNT => $request[AntomConstants::PAYMENT_AMOUNT],
            AntomConstants::PAYMENT_METHOD => $request[AntomConstants::PAYMENT_METHOD],
            AntomConstants::PAYMENT_REQUEST_ID => $request[AntomConstants::PAYMENT_REQUEST_ID],
            AntomConstants::ORDER => $request[AntomConstants::ORDER],
            AntomConstants::PRODUCT_CODE => $request[AntomConstants::PRODUCT_CODE],
            AntomConstants::SETTLEMENT_STRATEGY => $request[AntomConstants::SETTLEMENT_STRATEGY],
            AntomConstants::PAYMENT_REDIRECT_URL => $request[AntomConstants::PAYMENT_REDIRECT_URL],
        ];
        // set up optional parameters
        if (!empty($request[AntomConstants::PAYMENT_FACTOR])) {
            $body[AntomConstants::PAYMENT_FACTOR] = $request[AntomConstants::PAYMENT_FACTOR];
        }
        // set up paymentNotifyUrl
        if (!empty($request[AntomConstants::PAYMENT_NOTIFY_URL])) {
            $body[AntomConstants::PAYMENT_NOTIFY_URL] = $request[AntomConstants::PAYMENT_NOTIFY_URL];
        }
        $transfer = $this->transferBuilder
            ->setBody($body)
            ->build();
        return $transfer;
    }

    /**
     * Validate the request
     * @param $request
     * @return void
     */
    private function validateParams($request): void
    {
        AssertHelper::notEmpty($request, AntomConstants::HEADERS);
        AssertHelper::notEmpty($request, AntomConstants::METHOD);
        AssertHelper::notEmpty($request, AntomConstants::URI);
        AssertHelper::notEmpty($request, AntomConstants::CLIENT_CONFIG);
        AssertHelper::notEmpty($request, AntomConstants::ENV);
        AssertHelper::notEmpty($request, AntomConstants::PAYMENT_AMOUNT);
        AssertHelper::notEmpty($request, AntomConstants::PAYMENT_METHOD);
        if ($request[AntomConstants::PAYMENT_METHOD][AntomConstants::PAYMENT_METHOD_TYPE] == AntomConstants::CARD) {
            AssertHelper::notEmpty($request, AntomConstants::PAYMENT_FACTOR);
        }
        AssertHelper::notEmpty($request, AntomConstants::PAYMENT_REDIRECT_URL);
        AssertHelper::notEmpty($request, AntomConstants::PAYMENT_REQUEST_ID);
        AssertHelper::notEmpty($request, AntomConstants::PRODUCT_CODE);
        AssertHelper::notEmpty($request, AntomConstants::ORDER);
        AssertHelper::notEmpty($request, AntomConstants::SETTLEMENT_STRATEGY);
    }
}
