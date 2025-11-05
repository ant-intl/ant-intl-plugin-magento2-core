<?php

namespace Antom\Core\Logger;

use Antom\Core\Config\AntomConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Store\Model\StoreManagerInterface;
use Monolog\Logger;
use Monolog\LoggerFactory;

class AntomLogger
{
    const ANTOM_DEBUG = 101;
    const ANTOM_INFO = 203;
    const ANTOM_WARNING = 301;
    const ANTOM_ERROR = 401;

    /**
     * @var AntomConfig
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * @var array
     */
    private $handlers;

    /**
     * @var array
     */
    private $processors;

    /**
     * @param AntomConfig $config
     * @param StoreManagerInterface $storeManager
     * @param LoggerFactory $loggerFactory
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        AntomConfig $config,
        StoreManagerInterface $storeManager,
        LoggerFactory $loggerFactory,
        array $handlers = [],
        array $processors = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->loggerFactory = $loggerFactory;
        $this->handlers = $handlers;
        $this->processors = $processors;
    }


    /**
     * @param string $message
     * @param array $context
     * @return bool
     * @throws NoSuchEntityException
     */
    public function addAntomDebug(string $message, array $context = []): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        if ($this->config->debugLogsEnabled($storeId)) {
            $logger = $this->generateLogger(self::ANTOM_DEBUG);
            return $logger->addRecord(Logger::DEBUG, $message, $context);
        } else {
            return false;
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addAntomWarning(string $message, array $context = []): bool
    {
        $logger = $this->generateLogger(self::ANTOM_WARNING);
        return $logger->addRecord(Logger::WARNING, $message, $context);
    }


    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addAntomInfoLog(string $message, array $context = []): bool
    {
        $logger = $this->generateLogger(self::ANTOM_INFO);
        return $logger->addRecord(LOGGER::INFO, $message, $context);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        if (!is_string($message) && !(is_object($message) && method_exists($message, '__toString'))) {
            throw new \InvalidArgumentException('Message must be string or Stringable object');
        }

        $logger = $this->generateLogger(self::ANTOM_ERROR);
        $logger->addRecord(Logger::ERROR, (string)$message, $context);
    }

    public function getOrderContext(MagentoOrder $order): array
    {
        return [
            'orderId' => $order->getId(),
            'orderIncrementId' => $order->getIncrementId(),
            'orderState' => $order->getState(),
            'orderStatus' => $order->getStatus()
        ];
    }

    public function getInvoiceContext(MagentoOrder\Invoice $invoice): array
    {
        $stateName = $invoice->getStateName();

        return [
            'invoiceId' => $invoice->getEntityId(),
            'invoiceIncrementId' => $invoice->getIncrementId(),
            'invoiceState' => $invoice->getState(),
            'invoiceStateName' => $stateName instanceof Phrase ? $stateName->getText() : $stateName,
            'invoiceWasPayCalled' => $invoice->wasPayCalled(),
            'invoiceCanCapture' => $invoice->canCapture(),
            'invoiceCanCancel' => $invoice->canCancel(),
            'invoiceCanVoid' => $invoice->canVoid(),
            'invoiceCanRefund' => $invoice->canRefund()
        ];
    }

    /**
     * @param int $handler
     * @return Logger
     */
    private function generateLogger(int $handler): Logger
    {
        /** @var Logger $logger */
        $logger = $this->loggerFactory->create(['name' => 'Antom Logger']);

        foreach ($this->processors as $processor) {
            $logger->pushProcessor($processor);
        }

        switch ($handler) {
            case self::ANTOM_WARNING:
                $logger->pushHandler($this->handlers['antomWarning']);
                break;
            case self::ANTOM_INFO:
                $logger->pushHandler($this->handlers['antomInfo']);
                break;
            case self::ANTOM_ERROR:
                $logger->pushHandler($this->handlers['antomError']);
                break;
            case self::ANTOM_DEBUG:
            default:
                $logger->pushHandler($this->handlers['antomDebug']);
                break;
        }

        return $logger;
    }
}
