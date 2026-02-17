<?php

namespace Antom\Core\Model\Request;

use Request\pay\AlipayPaymentSessionRequest;

class AlipayCreatePaymentSessionRequest extends AlipayPaymentSessionRequest
{

    /**
     * @var string
     */
    public $productScene;

    /** @var ExtendInfo */
    public $extendInfo;

    /**
     * @var bool
     */
    public $requireUpdateAndConfirm;

    /**
     * @return string
     */
    public function getProductScene()
    {
        return $this->productScene;
    }

    /**
     * @param $productScene
     * @return void
     */
    public function setProductScene($productScene)
    {
        $this->productScene = $productScene;
    }

    public function getExtendInfo()
    {
        return $this->extendInfo;
    }

    public function setExtendInfo($extendInfo)
    {
        $this->extendInfo = $extendInfo;
    }

    public function getRequireUpdateAndConfirm()
    {
        return $this->requireUpdateAndConfirm;
    }

    public function setRequireUpdateAndConfirm($requireUpdateAndConfirm)
    {
        $this->requireUpdateAndConfirm = $requireUpdateAndConfirm;
    }
}

