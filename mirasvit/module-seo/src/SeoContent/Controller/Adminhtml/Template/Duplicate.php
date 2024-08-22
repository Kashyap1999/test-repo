<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-seo
 * @version   2.9.8
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\SeoContent\Controller\Adminhtml\Template;

use Mirasvit\SeoContent\Api\Data\TemplateInterface;
use Mirasvit\SeoContent\Controller\Adminhtml\Template;

class Duplicate extends Template
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam(TemplateInterface::ID);

        if ($id) {
            try {
                $modelData = $this->templateRepository->get($id)->getData();
                $newModel  = $this->templateRepository->create();

                if ($modelData[TemplateInterface::ID]) {
                    unset($modelData[TemplateInterface::ID]);
                }

                $newModel->setData($modelData);
                $this->templateRepository->save($newModel);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            $this->messageManager->addSuccessMessage(
                __('Template was duplicated successfully.')
            );
        } else {
            $this->messageManager->addErrorMessage(__('Please select template'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
