<?php

namespace HKGDealers\AdvancePayment\Helper;

use Zend\Log\Filter\Timestamp;
use Magento\Store\Model\StoreManagerInterface;

class MailSender
{

	public function sendMail($invoice,$url,$resample,$retest) {

		 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $inlineTranslation = $objectManager->create('\Magento\Framework\Translate\Inline\StateInterface');
        $transportBuilder = $objectManager->create('\Magento\Framework\Mail\Template\TransportBuilder');
        $scopeConfig  = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $loggerInterface =  $objectManager->create('\Psr\Log\LoggerInterface');
        $storeManager =  $objectManager->create('Magento\Store\Model\StoreManagerInterface');

        // $post = $this->getRequest()->getPost();
        try
        {
            // Send Mail
            $sentToEmail = $scopeConfig->getValue('hkgmain/hkg_emails/to_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $ccEmail = explode(",",$scopeConfig->getValue('hkgmain/hkg_emails/cc_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            $inlineTranslation->suspend();

            $sender = [
                // 'name' => $post['name'],
                // 'email' => $post['email']
                'name' => "Sales",
                'email' => "partner-info@report.epi-age.com"
            ];

            //$sentToEmail ='legred.vaz@sjinnovation.com';
            $sentToName = 'Admin';

            $transport = $transportBuilder
            ->setTemplateIdentifier('customemail_email_template')
            ->setTemplateOptions(
                [
                    'area' => 'frontend',
                    'store' => $storeManager->getStore()->getId()
                ]
                )
                ->setTemplateVars([
                    'grandtotal'  => $invoice->getGrandTotal(),
                    'invoiceid' => $invoice->getIncrementId(),
                    'orderid' => $invoice->getOrder()->getIncrementId(),
                    'qty' => $invoice->getTotalQty(),
                    'url'  => $url,
                    'resample' => $resample,
                    'retest' => $retest
                ])
                ->setFromByScope($sender)
                ->addTo($sentToEmail,$sentToName)
                ->addCc($ccEmail)
                ->getTransport();

                $transport->sendMessage();

                $inlineTranslation->resume();

        } catch(\Exception $e){
           
        }



	}
    
}
?>