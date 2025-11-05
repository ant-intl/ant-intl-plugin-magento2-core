<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomError extends Base
{
    protected $fileName = '/var/log/antom/error.log';
    protected $loggerType = Logger::ERROR;
    protected $level = Logger::ERROR;
}
