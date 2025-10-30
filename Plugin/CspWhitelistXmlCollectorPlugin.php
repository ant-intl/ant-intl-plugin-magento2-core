<?php

namespace Antom\Core\Plugin;


use Antom\Core\Config\AntomConfig;
use Magento\Csp\Model\Policy\FetchPolicy;

class CspWhitelistXmlCollectorPlugin {

    private $antomConfig;

    public function __construct(
        AntomConfig $antomConfig
    ) {
        $this->antomConfig = $antomConfig;
    }

    public function afterCollect($subject, array $result): array {

        $url = trim($this->antomConfig->getAntomGatewayUrl());
        if ($url) {
            $result[] = new FetchPolicy(
                'connect-src',
                false,
                [$url],
                [],
                false,
                false,
                false,
                [],
                [],
                false,
                false
            );
        }
        return $result;
    }
}
