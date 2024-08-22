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
use Magento\Sitemap\Model\ResourceModel\Cms\PageFactory;
use Mirasvit\Seo\Service\Alternate\CmsStrategy;
use Mirasvit\Seo\Service\Config\AlternateConfig;
use Mirasvit\SeoSitemap\Api\Repository\ProviderInterface;
use Mirasvit\SeoSitemap\Model\Config\CmsSitemapConfig;
use Mirasvit\SeoSitemap\Model\Config\LinkSitemapConfig;

class PageProvider implements ProviderInterface
{
    private $cmsFactory;

    private $dataHelper;

    private $cmsSitemapConfig;

    private $linkSitemapConfig;

    private $cmsStrategy;

    private $alternateConfig;

    public function __construct(
        CmsSitemapConfig $cmsSitemapConfig,
        LinkSitemapConfig $linkSitemapConfig,
        DataHelper $dataHelper,
        PageFactory $cmsFactory,
        CmsStrategy $cmsStrategy,
        AlternateConfig $alternateConfig
    ) {
        $this->dataHelper        = $dataHelper;
        $this->cmsFactory        = $cmsFactory;
        $this->cmsSitemapConfig  = $cmsSitemapConfig;
        $this->linkSitemapConfig = $linkSitemapConfig;
        $this->cmsStrategy       = $cmsStrategy;
        $this->alternateConfig   = $alternateConfig;
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return 'Magento_Cms';
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
        return __('Pages');
    }

    /**
     * @param int $storeId
     * @return array|DataObject
     */
    public function initSitemapItem($storeId)
    {
        return new DataObject([
            'changefreq' => $this->dataHelper->getPageChangefreq($storeId),
            'priority'   => $this->dataHelper->getPagePriority($storeId),
            'collection' => $this->getCmsPages($storeId),
        ]);
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getCmsPages($storeId)
    {
        $ignore   = $this->cmsSitemapConfig->getIgnoreCmsPages();
        $links    = $this->linkSitemapConfig->getAdditionalLinks($storeId);
        $cmsPages = $this->cmsFactory->create()->getCollection($storeId);

        foreach ($cmsPages as $cmsKey => $cms) {
            if (in_array($cms->getId(), $ignore)) {
                unset($cmsPages[$cmsKey]);
            }

            if ($cms->getUrl() == 'home') {
                $cms->setUrl('');
            }

            if ($this->alternateConfig->addHreflangToSitemap($storeId)) {
                $alternates = $this->cmsStrategy->getAlternateUrl([], $cms->getId(), $storeId);
                $cms->setAlternates($alternates);
            }
        }

        if ($links) {
            $cmsPages = array_merge($cmsPages, $links);
        }

        return $cmsPages;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getItems($storeId)
    {
        return [];
    }
}
