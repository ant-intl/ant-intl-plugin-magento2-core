<?php

namespace Antom\Core\Model\Request;

use Request\pay\AlipayPaymentSessionRequest;

class AlipayUpdatePaymentSessionRequest extends AlipayPaymentSessionRequest
{
    /**
     * @var ExtendInfo
     */
    public $extendInfo;

    /**
     * @var bool
     */
    public $confirm;

    /**
     * Get value of confirm
     *
     * @return bool
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Set value of confirm
     *
     * @param bool $confirm
     * @return void
     */
    public function setConfirm(bool $confirm)
    {
        $this->confirm = $confirm;
    }

    /**
     * Get value of extendInfo
     *
     * @return ExtendInfo
     */
    public function getExtendInfo()
    {
        return $this->extendInfo;
    }

    /**
     * Set value of extendInfo
     *
     * @param ExtendInfo $extendInfo
     * @return void
     */
    public function setExtendInfo($extendInfo)
    {
        $this->extendInfo = $extendInfo;
    }
}
