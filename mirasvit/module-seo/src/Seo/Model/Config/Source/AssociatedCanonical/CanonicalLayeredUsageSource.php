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


namespace Mirasvit\Seo\Model\Config\Source\AssociatedCanonical;


use Magento\Framework\Option\ArrayInterface;
use Mirasvit\Seo\Model\Config;

class CanonicalLayeredUsageSource implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Config::CANONICAL_LAYERED_USAGE_ALL, 'label' => __('Add all selected options')],
            ['value' => Config::CANONICAL_LAYERED_USAGE_ONE, 'label' => __('Add if only one option selected')],
        ];
    }
}
