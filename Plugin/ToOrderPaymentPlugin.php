<?php
namespace Antom\Core\Plugin;

use Antom\Core\Gateway\AntomConstants;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Antom\Core\Logger\AntomLogger;

class ToOrderPaymentPlugin
{
    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * Plugin copying data from payment to order
     *
     * @param AntomLogger $logger
     */
    public function __construct(AntomLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 在 convert 方法执行后拷贝 Antom 数据
     *
     * @param ToOrderPayment $subject
     * @param OrderPayment $orderPayment
     * @param Payment $quotePayment
     * @param array $data 去掉类型声明如果向后不兼容
     * @return OrderPayment
     */
    public function afterConvert(
        ToOrderPayment $subject,
        OrderPayment $orderPayment,
        Payment $quotePayment,
        array $data = []
    ) {
        // 只处理 Antom 支付方式
        if ($quotePayment->getMethod() !== 'antom_card') {
            return $orderPayment;
        }

        $this->logger->addAntomInfoLog('=== ToOrderPayment Plugin: Converting Antom payment ===');

        // 拷贝 Antom 数据
        $this->copyAntomData($quotePayment, $orderPayment);

        return $orderPayment;
    }

    /**
     * 拷贝 Antom 相关数据
     *
     * @param Payment $quotePayment
     * @param OrderPayment $orderPayment
     * @return void
     */
    private function copyAntomData(Payment $quotePayment, OrderPayment $orderPayment)
    {
        // 拷贝 antom_request_id
        $antomPaymentRequestId = $this->getAntomPaymentRequestId($quotePayment);

        if ($antomPaymentRequestId) {
            $orderPayment->setData(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $antomPaymentRequestId);
            $orderPayment->setAdditionalInformation(AntomConstants::ANTOM_PAYMENT_REQUEST_ID, $antomPaymentRequestId);

            $this->logger->addAntomInfoLog('Antom payment request ID copied via plugin', [
                'quote_payment_id' => $quotePayment->getId(),
                'antom_payment_request_id' => $antomPaymentRequestId,
                'order_payment_verification' => $orderPayment->getData(AntomConstants::ANTOM_PAYMENT_REQUEST_ID)
            ]);
        }
    }

    /**
     * 获取 antom_request_id
     *
     * @param Payment $quotePayment
     * @return array|mixed|null
     */
    private function getAntomPaymentRequestId(Payment $quotePayment)
    {
        $possibleFields = [
            'antom_payment_request_id'
        ];

        foreach ($possibleFields as $field) {
            // 检查直接属性
            $value = $quotePayment->getData($field);
            if ($value) {
                $this->logger->addAntomInfoLog("Found in direct property: $field", ['value' => $value]);
                return $value;
            }

            // 检查 additional_information
            $value = $quotePayment->getAdditionalInformation($field);
            if ($value) {
                $this->logger->addAntomInfoLog("Found in additional_information: $field", ['value' => $value]);
                return $value;
            }
        }

        $this->logger->addAntomWarning('Antom request ID not found', [
            'quote_payment_data' => array_keys($quotePayment->getData()),
            'additional_info_keys' => array_keys($quotePayment->getAdditionalInformation() ?: [])
        ]);

        return null;
    }
}
