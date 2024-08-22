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

namespace Mirasvit\SeoMarkup\Block\Og;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Directory\Block\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Tax\Model\Config;
use Mirasvit\Seo\Api\Service\StateServiceInterface;
use Mirasvit\SeoMarkup\Model\Config\ProductConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends AbstractBlock
{
    private $imageHelper;

    private $currency;

    private $outputHelper;

    private $stateService;

    private $productConfig;

    private $scopeConfig;

    private $productRepository;

    public function __construct(
        ImageHelper           $imageHelper,
        Currency              $currency,
        OutputHelper          $outputHelper,
        StateServiceInterface $stateService,
        ProductConfig         $productConfig,
        ScopeConfigInterface  $scopeConfig,
        ProductRepository     $productRepository,
        Template\Context      $context
    ) {
        $this->imageHelper       = $imageHelper;
        $this->currency          = $currency;
        $this->outputHelper      = $outputHelper;
        $this->stateService      = $stateService;
        $this->productConfig     = $productConfig;
        $this->scopeConfig       = $scopeConfig;
        $this->productRepository = $productRepository;

        parent::__construct($context);
    }

    protected function getMeta(): ?array
    {
        $product = $this->stateService->getProduct();

        if (!$product) {
            return null;
        }

        /** @var Store $store */
        $store = $this->_storeManager->getStore();

        $finalPrice = $this->getFinalPrice($product, $store);

        $meta = [
            'og:type'        => 'product',
            'og:url'         => $this->_urlBuilder->escape($product->getProductUrl()),
            'og:title'       => $this->pageConfig->getTitle()->get(),
            'og:description' => $this->outputHelper->productAttribute(
                $product,
                $product->getData('short_description'),
                'og:short_description'
            ),
            'og:image'       => $this->getImageUrl($product),
            'og:site_name'   => $store->getFrontendName(),
        ];

        $storeId = (int)$store->getId();

        if ($this->productConfig->isPriceEnabled($storeId)) {
            $meta['product:price:amount']   = $finalPrice;
            $meta['product:price:currency'] = $this->currency->getCurrentCurrencyCode();
        }

        if ($this->productConfig->isAvailabilityEnabled($storeId)) {
            $productAvailability = method_exists($product, 'isAvailable')
                ? $product->isAvailable()
                : $product->isInStock();

            $meta['product:availability'] = $productAvailability ? 'in stock' : 'out of stock';
        }

        return $meta;
    }

    /**
     * @param ProductModel $product
     */
    protected function getImageUrl(ProductInterface $product): string
    {
        return $this->imageHelper->init($product, 'product_base_image')
            ->keepAspectRatio(true)
            ->resize(800)
            ->getUrl();
    }

    protected function getFinalPrice(ProductInterface $product, Store $store): string
    {
        $priceAmount = $product->getPriceInfo()
            ->getPrice(FinalPrice::PRICE_CODE)
            ->getAmount();

        $taxesType = (int)$this->scopeConfig->getValue(
            'tax/display/type',
            ScopeInterface::SCOPE_STORES,
            $store
        );

        if (in_array($taxesType, [Config::DISPLAY_TYPE_INCLUDING_TAX, Config::DISPLAY_TYPE_BOTH])) {
            $finalPrice = $priceAmount->getValue();
        } else {
            $finalPrice = $priceAmount->getBaseAmount();
        }

        if ($product->getTypeId() === 'grouped') {
            $finalPrice = $this->getGroupedMinPrice($product, $store);
        }

        return number_format((float)$finalPrice, 2, '.', '');
    }

    private function getGroupedMinPrice(ProductInterface $product, Store $store): float
    {
        $typeInstance = $product->getTypeInstance();
        $childrenIds  = $typeInstance->getChildrenIds($product->getId());
        $minPrice     = 0;

        foreach (array_values($childrenIds)[0] as $childId) {
            $child      = $this->productRepository->getById($childId);
            $childPrice = $this->getFinalPrice($child, $store);
            $minPrice   = $minPrice == 0 ? $childPrice : min($minPrice, $childPrice);
        }

        return (float)$minPrice;
    }
}
