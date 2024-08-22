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


namespace Mirasvit\SeoAi\Service;


use Mirasvit\Core\Service\AbstractValidator;
use Mirasvit\SeoAi\Model\ConfigProvider;

class ValidationService extends AbstractValidator
{
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    public function testAiHelperMetaFix()
    {
        $issues = $this->configProvider->getCantRunReasons();

        foreach ($issues as $issue) {
            $this->addError($issue);
        }
    }
}
