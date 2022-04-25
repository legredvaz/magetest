<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\PDFInvoice\Controller\Adminhtml\Order\Invoice;
use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction extends \Magento\Sales\Controller\Adminhtml\Order\Invoice\PrintAction
{
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = $this->_objectManager->create(
                \Magento\Sales\Api\InvoiceRepositoryInterface::class
            )->get($invoiceId);
            if ($invoice) {
                $pdf = $this->_objectManager->create(\HKGDealers\PDFInvoice\Model\Order\Pdf\Invoice::class)->getPdf([$invoice]);
                $date = $this->_objectManager->get(
                    \Magento\Framework\Stdlib\DateTime\DateTime::class
                )->date('Y-m-d_H-i-s');
                $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];

                return $this->_fileFactory->create(
                    'invoice' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        } 
 
    }
}
