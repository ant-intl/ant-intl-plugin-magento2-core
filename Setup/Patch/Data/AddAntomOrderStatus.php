<?php

namespace Antom\Core\Setup\Patch\Data;

use Antom\Core\Logger\AntomLogger;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddAntomOrderStatus implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AntomLogger $logger
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, AntomLogger $logger)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * Apply the order status patch
     *
     * @return void
     */
    public function apply()
    {
        $this->logger->addAntomInfoLog("Started patching......." .
            (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        try {
            $statuses = [
                // status_code => [label, state, is_default, visible_on_front]
                'antom_element_card_failed' => [
                    'label' => 'Antom Element Card Payment Failure',
                    'state' => 'canceled',
                    'is_default' => 0,
                    'visible_on_front' => 0
                ]
            ];
            // 获取连接
            $connection = $this->moduleDataSetup->getConnection();
            foreach ($statuses as $code => $config) {

                $connection->insertOnDuplicate(
                    $this->moduleDataSetup->getTable('sales_order_status'),
                    [
                        'status' => $code,
                        'label'  => (string)$config['label'] // 确保是字符串
                    ],
                    ['label'] // 冲突时更新 label
                );
                $connection->insertOnDuplicate(
                    $this->moduleDataSetup->getTable('sales_order_status_state'),
                    [
                        'status' => $code,
                        'state' => $config['state'],
                        'is_default' => $config['is_default'],
                        'visible_on_front' => $config['visible_on_front']
                    ],
                    ['state', 'is_default', 'visible_on_front']
                );
            }
        } catch (\Throwable $e) {
            throw $e;
        }
        $this->logger->addAntomInfoLog("Successfully added patching "
            . $statuses['antom_element_card_failed']['label']);
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get Alias
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
