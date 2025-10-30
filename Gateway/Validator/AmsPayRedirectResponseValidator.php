<?php

namespace Antom\Core\Gateway\Validator;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use Magento\Framework\Exception\ValidatorException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class AmsPayRedirectResponseValidator extends AbstractValidator
{
    /**
     * @var AntomLogger
     */
    private $logger;
    /**
     * @var AntomConfig
     */
    private $antomConfig;

    /**
     * AmsPayRedirectResponseValidator constructor
     * @param ResultInterfaceFactory $resultFactory
     * @param AntomLogger $logger
     * @param AntomConfig $antomConfig
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        AntomLogger            $logger,
        AntomConfig            $antomConfig
    )
    {
        $this->logger = $logger;
        $this->antomConfig = $antomConfig;
        parent::__construct($resultFactory);
    }

    /**
     * Validate the redirect payment response from Antom
     * @param array $validationSubject
     * @return ResultInterface
     * @throws ValidatorException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        $paymentDO = SubjectReader::readPayment($validationSubject);
        $paymentMethod = $paymentDO->getPayment()->getMethodInstance()->getCode();
        $storeId = $paymentDO->getOrder()->getStoreId();
        $paymentRequestId = $paymentDO->getPayment()->getAdditionalInformation(AntomConstants::PAYMENT_REQUEST_ID);

        if (empty($response)) {
            throw new ValidatorException(__("No responses were provided"));
        }

        if (empty($response[AntomConstants::RESULT])) {
            $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
            throw new ValidatorException(__('Invalid response, result obj should not be empty!'));
        }
        if (strcmp($response[AntomConstants::RESULT][AntomConstants::RESULT_STATUS], AntomConstants::F) == 0) {
            $orderId = SubjectReader::readPayment($validationSubject)->getOrder()->getOrderIncrementId();
            $msg = sprintf(
                'payment was declined, response for Antom is %s, orderId is %s',
                json_encode($response),
                $orderId
            );
            $this->logger->addAntomWarning($msg);
        } else {
            if (strcmp($paymentMethod, AntomConstants::MAGENTO_ALIPAY_CN) == 0) {
                // Given the paymentMethod is ALIPAY_CN, the resultCode should be PAYMENT_IN_PROCESS
                if (strcmp($response[AntomConstants::RESULT][AntomConstants::RESULT_CODE],
                        AntomConstants::PAYMENT_IN_PROCESS) != 0) {
                    $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
                    throw new ValidatorException(__("Invalid response code, with paymentMethod being ALIPAY_CN"));
                }
                // normalUrl is required for ALIPAY_CN redirect payment
                if (empty($response[AntomConstants::NORMAL_URL])) {
                    $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
                    throw new ValidatorException(__("The normalUrl is empty, this is required with ALIPAY_CN"));
                }
            }

            if (strcmp($paymentMethod, AntomConstants::MAGENTO_ANTOM_CARD) == 0) {
                // TODO: check result status U
                if (strcmp($response[AntomConstants::RESULT][AntomConstants::RESULT_CODE],
                        AntomConstants::U) == 0) {
                    // 3DS case
                    // TODO: add 3d cardinal link check
                    if (empty($response[AntomConstants::NORMAL_URL])) {
                        $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
                        throw new ValidatorException(__("The normalUrl is empty,
                         this is required with Card Payment for 3ds authentication"));
                    }
                }
            }

            // paymentRequestId in the response should match the paymentRequestId in the request
            if (empty($response[AntomConstants::PAYMENT_REQUEST_ID])
                || strcmp($response[AntomConstants::PAYMENT_REQUEST_ID], $paymentRequestId) != 0) {
                $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
                throw new ValidatorException(
                    __('The paymentRequestId in the response does not match the one in the request')
                );
            }
            // paymentId is also required in the response
            if (empty($response[AntomConstants::PAYMENT_ID])) {
                $this->printDebugLogWhenDebugMode(json_encode($response), $storeId);
                throw new ValidatorException(__('The paymentId is empty, this is required'));
            }

        }
        return $this->createResult(true);
    }

    /**
     * Print the log when Debug Mode is on.
     * @param $msg
     * @param $storeId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function printDebugLogWhenDebugMode($msg, $storeId = null)
    {
        if ($this->antomConfig->isDebug($storeId)) {
            $this->logger->addAntomDebug($msg, ["storeId" => $storeId]);
        }
    }

}
