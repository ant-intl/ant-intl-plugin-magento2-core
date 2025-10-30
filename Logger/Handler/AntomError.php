<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Level;

class AntomError extends Base
{
    protected $fileName = '/var/log/antom/error.log';
    protected $loggerType = Level::Error;
    protected Level $level = Level::Error;
}
