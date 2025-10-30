<?php

namespace Antom\Core\Logger;

use Antom\Core\Config\AntomConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Store\Model\StoreManagerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LoggerFactory;

class AntomLogger
{
    const ANTOM_DEBUG = 101;
    const ANTOM_INFO = 203;
    const ANTOM_WARNING = 301;
    const ANTOM_ERROR = 401;

    /**
     * @param AntomConfig $config
     * @param StoreManagerInterface $storeManager
     * @param LoggerFactory $loggerFactory
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        private readonly AntomConfig                $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerFactory         $loggerFactory,
        private array                          $handlers = [],
        private array                          $processors = []
    )
    {
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
            return $logger->addRecord(Level::Debug, $message, $context);
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
        return $logger->addRecord(Level::Warning, $message, $context);
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
        return $logger->addRecord(Level::Info, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $logger = $this->generateLogger(self::ANTOM_ERROR);
        $logger->addRecord(Level::Error, $message, $context);
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
