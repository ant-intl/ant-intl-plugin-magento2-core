<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomDebug extends Base
{
    protected $fileName = '/var/log/antom/debug.log';
    protected $loggerType = Logger::DEBUG;
    protected $level = Logger::DEBUG;
}
