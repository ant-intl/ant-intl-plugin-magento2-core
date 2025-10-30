<?php

namespace Antom\Core\Helper;

use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Logger\AntomLogger;
use InvalidArgumentException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequestHelper extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AntomLogger
     */
    private $logger;
    /**
     * Currency map to minor unit digits
     */
    const CURRENCY_MINOR_UNIT = [
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'CNY' => 2,
        'AUD' => 2,
        'CAD' => 2,
        'JPY' => 0,
        'KRW' => 0,
        'VND' => 0,
        'KWD' => 3
    ];

    /**
     * RequestHelper constructor
     *
     * @param Context $context
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        AntomLogger           $antomLogger
    )
    {
        $this->storeManager = $storeManager;
        $this->logger = $antomLogger;
        parent::__construct($context);
    }

    /**
     * Used when compose the Antom Pay request, this will build the env.
     * @return array
     */
    public function composeEnvInfo()
    {
        $browserInfo = [];
        $env = [];
        $userAgent = $this->_request->getServer('HTTP_USER_AGENT');
        $acceptHeader = $this->_request->getServer('HTTP_ACCEPT');
        $clientIp = $this->_request->getServer('REMOTE_ADDR');
        $language = $this->_request->getServer('HTTP_ACCEPT_LANGUAGE');

        if (!empty($clientIp)) {
            $env[AntomConstants::CLIENT_IP] = $clientIp;
        }

        if (!empty($language)) {
            $browserInfo[AntomConstants::LANGUAGE] = $language;
        }

        $terminalType = 'WEB';
        if (preg_match('/(Mobile|Android|iPhone|iPad)/i', $userAgent)) {
            $terminalType = 'WAP';
            $osType = 'IOS';
            if (stripos($userAgent, 'android') !== false) {
                $osType = 'ANDROID';
            }
            $env[AntomConstants::OS_TYPE] = $osType;

        }
        $env[AntomConstants::TERMINAL_TYPE] = $terminalType;
        if (!empty($acceptHeader)) {
            $browserInfo['acceptHeader'] = $acceptHeader;
        }

        $env[AntomConstants::BROWSER_INFO] = $browserInfo;
        return $env;
    }

    /**
     * Get the domain of the store
     * @return string
     */
    public function getDomain(): string
    {
        $domain = '';
        try {
            $domain = $this->storeManager->getStore()->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->logger->addAntomWarning('can not find domain from [storeManager], use [_request]. error: '
                . $e->getMessage());
        }
        if (empty($domain)) {
            $domain = $this->_request->isSecure() ? 'https://' : 'http://';
            $domain .= $this->_request->getServer('HTTP_HOST');
        }
        return rtrim($domain, '/');
    }

    /**
     * Determine whether the paymentMethod is Alipay_CN
     * @param string $paymentMethod
     * @return bool
     */
    public function isAlipayCnPaymentMethod(string $paymentMethod): bool
    {
        return $paymentMethod == AntomConstants::MAGENTO_ALIPAY_CN;
    }

    /**
     * Determine whether the paymentMethod is Antom Card Payment
     * @param string $paymentMethod
     * @return bool
     */
    public function isCardPaymentMethod(string $paymentMethod): bool
    {
        return $paymentMethod == AntomConstants::MAGENTO_ANTOM_CARD;
    }


    /**
     * Get order amount from order
     * @param OrderAdapterInterface $order
     * @return array
     */
    public function getOrderAmount(OrderAdapterInterface $order): array
    {
        $currencyCode = $order->getCurrencyCode();
        $amount = $order->getGrandTotalAmount();
        if (!array_key_exists($currencyCode, self::CURRENCY_MINOR_UNIT)) {
            throw new InvalidArgumentException(__('Currency is not supported for this order.'));
        }
        $decimals = self::CURRENCY_MINOR_UNIT[$currencyCode] ?? 2;
        $factor = pow(10, $decimals);
        $amount = (int)round($amount * $factor);
        return [
            AntomConstants::CURRENCY => $currencyCode,
            AntomConstants::VALUE => $amount
        ];
    }

    /**
     * Convert the money amount to decimal
     * @param array $amount
     * @return array
     */
    public function amountConvert(array $amount)
    {
        $currencyCode = $amount[AntomConstants::CURRENCY];
        $value = $amount[AntomConstants::VALUE];
        if (!array_key_exists($currencyCode, self::CURRENCY_MINOR_UNIT)) {
            throw new InvalidArgumentException(__('Currency is not supported for this order.'));
        }
        $decimals = self::CURRENCY_MINOR_UNIT[$currencyCode];
        $factor = pow(10, $decimals);
        $value = bcdiv($value, $factor, $decimals);
        return [
            AntomConstants::CURRENCY => $currencyCode,
            AntomConstants::AMOUNT => $value
        ];
    }
}
