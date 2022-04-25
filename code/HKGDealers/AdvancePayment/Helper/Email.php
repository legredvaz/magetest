<?php
 
namespace HKGDealers\AdvancePayment\Helper;
 
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
 
/**
 * Class ModifyOrders
 *
 * @package Ayakil\CustomEmail\Helper
 */
 
class Email extends AbstractHelper
{
    protected $inlineTranslation;
    protected $escaper;
    protected $transportBuilder;
    protected $logger;
    protected $scopeConfig;
    protected $storeManager;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
        $this->scopeConfig = $scopeConfig; 
        $this->storeManager = $storeManager;
 
    }
// You can pass whatever the arguments here according to your requirement.
public function SendEmail( $customerEmail )
    {
        try {
            $this->inlineTranslation->suspend();
            $sender_name = "HKG Owner";
            $sender_email = "partner-info@report.epi-age.com";
            $sender = [
                'name' => $sender_name,
                'email' => $sender_email
            ];
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('hkg_email_invoice_template')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId(),
                    ]
                )
                ->setFrom($sender)
                ->addTo($customerEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}