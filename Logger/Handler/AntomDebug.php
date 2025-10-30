<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Level;

class AntomDebug extends Base
{
    protected $fileName = '/var/log/antom/debug.log';
    protected $loggerType = Level::Debug;
    protected Level $level = Level::Debug;
}
