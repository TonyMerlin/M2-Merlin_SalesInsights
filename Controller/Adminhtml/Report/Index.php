<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Merlin_SalesInsights::report';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Merlin_SalesInsights::report');
        $page->getConfig()->getTitle()->prepend(__('Sales Insights'));
        return $page;
    }
}
