<?php

namespace Antom\Core\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Store\Model\ScopeInterface;

class AntomConfig
{
    const DEFAULT_PATH_PATTERN = 'antom/general/%s';
    const ANTOM_GATEWAY_URL = 'antom_gateway_url';
    const ANTOM_CLIENT_ID = 'antom_client_id';
    const ANTOM_PUBLIC_KEY = 'antom_public_key';
    const MERCHANT_PRIVATE_KEY = 'merchant_private_key';
    const ANTOM_SANDBOX_GATEWAY_URL = 'antom_sandbox_gateway_url';
    const ANTOM_SANDBOX_CLIENT_ID = 'antom_sandbox_client_id';
    const ANTOM_SANDBOX_PUBLIC_KEY = 'antom_sandbox_public_key';
    const MERCHANT_SANDBOX_PRIVATE_KEY = 'merchant_sandbox_private_key';

    const DEFAULT_MIXED_CARD_PATH_PATTERN = 'payment/%s/active';
    const ANTOM_MIXED_CARD = ['antom_card_visa', 'antom_card_mastercard', 'antom_card_amex', 'antom_card_diner',
        'antom_card_discover', 'antom_card_unionpay', 'antom_card_jcb', 'antom_card_dinser'];

    const ENV = 'environment';
    const DEBUG = 'debug';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Encryptor
     */
    private $encryptor;



    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Encryptor $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Encryptor            $encryptor
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isDebug($storeId = null): bool
    {
        return $this->getConfig(self::DEBUG, $storeId, true);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isLiveEnv($storeId = null): bool
    {
        return $this->getConfig(self::ENV, $storeId, true);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getAntomGatewayUrl($storeId = null): string
    {
        $url = $this->isLiveEnv($storeId)
            ? $this->getConfig(self::ANTOM_GATEWAY_URL, $storeId)
            : $this->getConfig(self::ANTOM_SANDBOX_GATEWAY_URL, $storeId);
        return $url == null ? "" : trim($url);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getAntomClientId($storeId = null): string
    {
        $clientId = $this->isLiveEnv($storeId)
            ? $this->getConfig(self::ANTOM_CLIENT_ID, $storeId)
            : $this->getConfig(self::ANTOM_SANDBOX_CLIENT_ID, $storeId);
        return $clientId == null ? "" : trim($clientId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getMerchantPrivateKey($storeId = null, $liveMode = null): string
    {
        $isLiveEnv = $this->isLiveEnv($storeId);
        if ($liveMode != null) {
            $isLiveEnv = $liveMode;
        }

        $res = $isLiveEnv
            ? $this->getConfig(self::MERCHANT_PRIVATE_KEY, $storeId)
            : $this->getConfig(self::MERCHANT_SANDBOX_PRIVATE_KEY, $storeId);

        return $res == null ? "" : $this->encryptor->decrypt($res);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getAntomPublicKey($storeId = null): string
    {
        $res = $this->isLiveEnv($storeId)
            ? $this->getConfig(self::ANTOM_PUBLIC_KEY, $storeId)
            : $this->getConfig(self::ANTOM_SANDBOX_PUBLIC_KEY, $storeId);
        return $res == null ? "" : trim($res);
    }

    /**
     * Retrieve enabled cards from the admin configuration
     *
     * @param $storeId
     * @return array
     */
    public function getEnabledCards($storeId = null) {
        $enabled = $this->getConfig("antom_card", $storeId, false,self::DEFAULT_MIXED_CARD_PATH_PATTERN);
        $enabledCards = [];
        if ($enabled) {
            foreach (self::ANTOM_MIXED_CARD as $card) {
                $enabledCard = $this->getConfig($card, $storeId, false,self::DEFAULT_MIXED_CARD_PATH_PATTERN);
                if ($enabledCard) {
                    $enabledCards[] = $card;
                }
            }
        }
        return $enabledCards;
    }

    /**
     * Retrieve config based on the storeId, if null, use the default scope
     *
     * @param string $field
     * @param int|string|null $storeId
     * @param bool $flag true if the field is set to 0/1, false if the field is other type
     * @return mixed
     */
    private function getConfig(string $field, int|string|null $storeId, bool $flag = false,
                               string $pattern = self::DEFAULT_PATH_PATTERN): mixed
    {
        $path = sprintf($pattern, $field);

        $scope = $storeId === null
            ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            : ScopeInterface::SCOPE_STORE;

        if (!$flag) {
            return $this->scopeConfig->getValue($path, $scope, $storeId);
        } else {
            return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
        }
    }
}
