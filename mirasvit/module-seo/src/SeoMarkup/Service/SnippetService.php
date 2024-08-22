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

namespace Mirasvit\SeoMarkup\Service;

class SnippetService
{
    public function extendRichSnippet(array $data, array $update, bool $isOverrideEnabled): array
    {
        foreach ($update as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->extendRichSnippet($data[$key] ?? [], $value, $isOverrideEnabled);
            } elseif ($isOverrideEnabled || !isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
