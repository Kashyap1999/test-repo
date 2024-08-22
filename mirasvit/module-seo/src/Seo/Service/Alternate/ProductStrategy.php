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


declare(strict_types=1);

namespace Mirasvit\Seo\Service\Alternate;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite as UrlRewrite;
use Magento\Catalog\Model\Product\Visibility;
use Mirasvit\Seo\Api\Service\Alternate\StrategyInterface;
use Mirasvit\Seo\Api\Service\Alternate\UrlInterface;
use Magento\Framework\Registry;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Framework\App\Request\Http;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductStrategy implements StrategyInterface
{
    protected $url;

    protected $registry;

    protected $urlFinder;

    protected $request;

    protected $productRepository;

    protected $storeManager;

    protected $frontNameResolver;

    public function __construct(
        UrlInterface $url,
        Registry $registry,
        UrlFinderInterface $urlFinder,
        Http $request,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        FrontNameResolver $frontNameResolver
    ) {
        $this->url                  = $url;
        $this->registry             = $registry;
        $this->urlFinder            = $urlFinder;
        $this->request              = $request;
        $this->productRepository    = $productRepository;
        $this->storeManager         = $storeManager;
        $this->frontNameResolver    = $frontNameResolver;
    }

    public function getStoreUrls(): array
    {
        $storeUrls = $this->url->getStoresCurrentUrl();

        return $this->getAlternateUrl($storeUrls);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getAlternateUrl(array $storeUrls, int $entityId = null, int $storeId = null): array
    {
        if ($entityId) {
            $productId = $entityId;
            $stores    = $this->url->getStoresByStoreId($storeId);
        } else {
            $productId = $this->registry->registry('current_product')->getId();
            $stores    = $this->url->getStores();
        }

        foreach ($stores as $storeId => $store) {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId,false, $storeId);

            if ($product->getData('visibility') == Visibility::VISIBILITY_NOT_VISIBLE || !in_array($storeId, $product->getStoreIds())) {
                unset($storeUrls[$storeId]);
                continue;
            }

            $rewriteObject = null;

            if ($entityId) {
                $rewriteObject = $this->urlFinder->findOneByData([
                    UrlRewrite::ENTITY_ID => $productId,
                    UrlRewrite::ENTITY_TYPE => 'product',
                    UrlRewrite::STORE_ID => $storeId,
                ]);
            } else {
                $idPath = $this->request->getPathInfo();

                if ($idPath && str_contains($idPath, (string)$productId)) {
                    $rewriteObject = $this->urlFinder->findOneByData([
                        UrlRewrite::TARGET_PATH => trim($idPath, '/'),
                        UrlRewrite::STORE_ID => $storeId,
                    ]);
                }
            }

            if ($rewriteObject && ($requestPath = $rewriteObject->getRequestPath())) {
                $storeUrls[$storeId] = $store->getBaseUrl() . $requestPath . $this->url->getUrlAddition($store);
            } elseif (!$rewriteObject) {
                $url = $product->getUrlInStore();

                // some products, such as those without a URL key or any rewrites created, may include a URL with an admin path
                if (str_contains($url, $this->frontNameResolver->getFrontName())) {
                    $url = $store->getBaseUrl() . 'catalog/product/view/id/' . $product->getId();
                }

                $storeUrls[$storeId] = $url;
            }
        }

        if (count($storeUrls) === 1) {
            $storeUrls = []; // page doesn't have variations
        }

        return $storeUrls;
    }
}
