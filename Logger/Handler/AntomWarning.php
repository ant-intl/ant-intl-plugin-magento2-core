<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Level;

class AntomWarning extends Base
{
    protected $fileName = '/var/log/antom/warning.log';
    protected $loggerType = Level::Warning;
    protected Level $level = Level::Warning;
}
