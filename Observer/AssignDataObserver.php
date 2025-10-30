<?php
namespace Antom\Core\Observer;

use Antom\Core\Gateway\AntomConstants;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AssignDataObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $payment = $observer->getEvent()->getPaymentModel();
        $data = $observer->getEvent()->getData('data');

        $additionalData = $data->getAdditionalData();

        if ($payment->getMethodInstance()->getCode() == 'antom_card'
            && is_array($additionalData) && isset($additionalData[AntomConstants::CARD_TOKEN])) {
            $payment->setAdditionalInformation(AntomConstants::CARD_TOKEN, $additionalData[AntomConstants::CARD_TOKEN]);
        }
    }
}
