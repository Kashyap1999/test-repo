<?php

declare(strict_types=1);

namespace Mirasvit\SeoToolbar\Controller\Toolbar;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Mirasvit\SeoToolbar\Model\Config;
use Magento\Framework\Controller\Result\JsonFactory;

class ToolbarAjax extends Action
{
     /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

   
    private $config;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Config $config,
        Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $isToolBarAllowed = $this->config->isToolbarAllowed();

        return $result->setData(['isToolBarAllowed' => $isToolBarAllowed]);
    }
}
