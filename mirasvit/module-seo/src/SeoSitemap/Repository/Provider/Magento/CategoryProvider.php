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



namespace Mirasvit\SeoSitemap\Repository\Provider\Magento;

use Magento\Framework\DataObject;
use Magento\Sitemap\Helper\Data as DataHelper;
use Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory;
use Mirasvit\Seo\Service\Alternate\CategoryStrategy;
use Mirasvit\Seo\Service\Config\AlternateConfig;
use Mirasvit\SeoSitemap\Api\Repository\ProviderInterface;

class CategoryProvider implements ProviderInterface
{
    private $dataHelper;

    private $categoryFactory;

    private $categoryStrategy;

    private $alternateConfig;

    public function __construct(
        DataHelper $dataHelper,
        CategoryFactory $categoryFactory,
        CategoryStrategy $categoryStrategy,
        AlternateConfig $alternateConfig
    ) {
        $this->dataHelper       = $dataHelper;
        $this->categoryFactory  = $categoryFactory;
        $this->categoryStrategy = $categoryStrategy;
        $this->alternateConfig  = $alternateConfig;
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return "Magento_Catalog";
    }

    /**
     * @return bool
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return __('Categories');
    }

    /**
     * @param int $storeId
     * @return array|DataObject
     */
    public function initSitemapItem($storeId)
    {
        return new DataObject([
            'changefreq' => $this->dataHelper->getCategoryChangefreq($storeId),
            'priority'   => $this->dataHelper->getCategoryPriority($storeId),
            'collection' => $this->getCategoryItems($storeId)
        ]);
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getItems($storeId)
    {
        return [];
    }

    private function getCategoryItems($storeId)
    {
        $categories = $this->categoryFactory->create()->getCollection($storeId);

        if ($this->alternateConfig->addHreflangToSitemap($storeId)) {
            foreach ($categories as $category) {
                $alternates = $this->categoryStrategy->getAlternateUrl([], $category->getId(), $storeId);
                $category->setAlternates($alternates);
            }
        }

        return $categories;
    }
}
