<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AntomWarning extends Base
{
    protected $fileName = '/var/log/antom/warning.log';
    protected $loggerType = Logger::WARNING;
    protected $level = Logger::WARNING;
}
