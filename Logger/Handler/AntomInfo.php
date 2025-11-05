<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomInfo extends Base
{
    protected $fileName = '/var/log/antom/info.log';
    protected $loggerType = Logger::INFO;
    protected $level = Logger::INFO;
}
