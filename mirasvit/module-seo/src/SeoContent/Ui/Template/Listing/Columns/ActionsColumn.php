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



namespace Mirasvit\SeoContent\Ui\Template\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mirasvit\SeoContent\Api\Data\TemplateInterface;

class ActionsColumn extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * ActionsColumn constructor.
     * @param UrlInterface $urlBuilder
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = [
                    'edit'   => [
                        'href'  => $this->urlBuilder->getUrl('seo_content/template/edit', [
                            TemplateInterface::ID => $item[TemplateInterface::ID],
                        ]),
                        'label' => __('Edit'),
                    ],
                    'duplicate'   => [
                        'href'  => $this->urlBuilder->getUrl('seo_content/template/duplicate', [
                            TemplateInterface::ID => $item[TemplateInterface::ID],
                        ]),
                        'label' => __('Duplicate'),
                    ],
                    'delete' => [
                        'href'    => $this->urlBuilder->getUrl('seo_content/template/delete', [
                            TemplateInterface::ID => $item[TemplateInterface::ID],
                        ]),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete template?'),
                        ],
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
