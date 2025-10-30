<?php

namespace Antom\Core\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Level;

class AntomInfo extends Base
{
    protected $fileName = '/var/log/antom/info.log';
    protected $loggerType = Level::Info;
    protected Level $level = Level::Info;
}
