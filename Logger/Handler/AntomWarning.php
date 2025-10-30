<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomWarning extends Base
{
    protected $fileName = '/var/log/antom/warning.log';
    protected $loggerType = Logger::WARNING;

    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
                                                      $filePath = null,
                                                      $fileName = null
    ) {
        // 动态设置日志级别
        if (class_exists('\Monolog\Level')) {
            // Monolog 3.x (Magento 2.4.6+)
            $this->loggerType = \Monolog\Level::Warning;
        } else {
            // Monolog 2.x (Magento 2.4.5-)
            $this->loggerType = Logger::WARNING;
        }

        parent::__construct($filesystem, $filePath, $fileName);
    }
}
