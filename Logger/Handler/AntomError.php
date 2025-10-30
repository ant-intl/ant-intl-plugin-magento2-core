<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomError extends Base
{
    protected $fileName = '/var/log/antom/error.log';
    protected $loggerType = Logger::ERROR;

    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
                                                      $filePath = null,
                                                      $fileName = null
    ) {
        // 动态设置日志级别
        if (class_exists('\Monolog\Level')) {
            // Monolog 3.x (Magento 2.4.6+)
            $this->loggerType = \Monolog\Level::Error;
        } else {
            // Monolog 2.x (Magento 2.4.5-)
            $this->loggerType = Logger::ERROR;
        }

        parent::__construct($filesystem, $filePath, $fileName);
    }
}
