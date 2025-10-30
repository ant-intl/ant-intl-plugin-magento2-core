<?php
declare(strict_types=1);

namespace Antom\Core\CustomerData;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Logger\AntomLogger;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Model\Quote;

/**
 * The antom-payment-request for section load api
 * /customer/section/load/?sections=antom-payment-request
 */
class PaymentRequest implements SectionSourceInterface
{
    /**
     * @var Quote|null
     */
    private $quote = null;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AntomConfig
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Session
     */
    private $session;

    /**
     * PaymentRequest constructor.
     *
     * @param AntomLogger $logger
     * @param AntomConfig $config
     * @param ResolverInterface $localeResolver
     * @param Session $session
     */
    public function __construct(
        AntomLogger $logger,
        AntomConfig $config,
        ResolverInterface $localeResolver,
        Session $session
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->session = $session;
    }

    /**
     * Load all the customer data that is necessary for
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function getSectionData(): array
    {
        $storeId = $this->getStoreIdFromQuote();
        $quote = $this->getQuote();

        $result = [
            "environment" => $this->config->isLiveEnv($storeId) ? 'live' : 'test',
            "locale" => $this->localeResolver->getLocale(),
            "cartTotal" => $quote->getGrandTotal(),
            "currency" => $quote->getCurrency()->getQuoteCurrencyCode() ?? '',
            "storeId" => $storeId,
            "clientId" => $this->config->getAntomClientId(),
            'antomPublicKey' => $this->config->getAntomPublicKey(),
            'enabledCards' => $this->config->getEnabledCards(),
            'gatewayUrl' => $this->config->getAntomGatewayUrl(),
        ];

        return $result;
    }

    /**
     * Retrieve the quote from the checkout session
     *
     * @return Quote|null
     */
    private function getQuote(): ?Quote
    {
        try {
            if (!$this->quote) {
                $this->quote = $this->session->getQuote();
            }
        } catch (LocalizedException | NoSuchEntityException $exception) {
            $this->logger->logException($exception);
        }

        return $this->quote;
    }

    /**
     * Get the Store ID from the quote
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getStoreIdFromQuote():?int
    {
        if (method_exists($this->getQuote(), 'getStoreId')) {
            return (int)$this->getQuote()->getStoreId();
        }

        return null;
    }
}
