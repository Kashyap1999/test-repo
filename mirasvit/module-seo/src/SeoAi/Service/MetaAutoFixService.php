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


use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\SeoAi\Model\ConfigProvider;
use Mirasvit\SeoAudit\Api\Data\CheckResultInterface;
use Mirasvit\SeoAudit\Api\Data\UrlInterface;
use Mirasvit\SeoAudit\Repository\CheckResultRepository;
use Mirasvit\SeoAudit\Repository\UrlRepoitory;
use Mirasvit\SeoContent\Api\Data\RewriteInterface;
use Mirasvit\SeoContent\Repository\RewriteRepository as SeoRewriteRepository;
use Symfony\Component\Console\Output\OutputInterface;

class MetaAutoFixService
{
    const MAX_ATTEMPTS = 3;

    const DELAY = 10;

    private $attempt = 0;

    private $configProvider;

    private $completionsService;

    private $urlRepository;

    private $checkResultRepository;

    private $seoRewriteRepository;

    private $storeManager;

    private $promptService;

    public function __construct(
        ConfigProvider $configProvider,
        CompletionsService $completionsService,
        UrlRepoitory $urlRepoitory,
        CheckResultRepository $checkResultRepository,
        SeoRewriteRepository $seoRewriteRepository,
        StoreManagerInterface $storeManager,
        PromptService $promptService
    ) {
        $this->configProvider        = $configProvider;
        $this->completionsService    = $completionsService;
        $this->urlRepository         = $urlRepoitory;
        $this->checkResultRepository = $checkResultRepository;
        $this->seoRewriteRepository  = $seoRewriteRepository;
        $this->storeManager          = $storeManager;
        $this->promptService         = $promptService;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processUrls(int $limit = 1000, int $timeLimit = null, ?OutputInterface $output = null)
    {
        $start = microtime(true);

        $urls = $this->urlRepository->getCollection()
            ->addFieldToFilter('type', 'page')
            ->addFieldToFilter('status_code', 200)
            ->addFieldToFilter('status', 'finished')
            ->setOrder('RAND()')
            ->setPageSize($limit)
            ->setCurPage(1);

        /** @var UrlInterface $url */
        foreach ($urls as $url) {
            if ($timeLimit && (microtime(true) - $start) >= $timeLimit) {
                $this->printStatus($output, '<info>Process stopped because time limit reached</info>');
                break;
            }

            $this->printStatus($output, '<info>-----------------------------------</info>');
            $this->printStatus($output, '<info>URL:</info> ' . $url->getUrl());

            $storeId = $this->resolveStoreId($url->getUrl());

            if (is_null($storeId)) {
                $this->printStatus($output, 'URL does not belong to any store. Skipped.');
                continue;
            }

            $meta = [
                'meta_title'       => '',
                'meta_description' => ''
            ];

            $relativeUrl = str_replace(
                rtrim($this->storeManager->getStore($storeId)->getBaseUrl(), '/') . '/',
                '/',
                $url->getUrl()
            );

            /** @var RewriteInterface $seoRewrite */
            $seoRewrite = $this->seoRewriteRepository->getCollection()
                ->addFieldToFilter('url', $relativeUrl)
                ->addStoreFilter($storeId)
                ->getLastItem();

            if (
                $seoRewrite && $seoRewrite->getId()
                && (bool)$seoRewrite->getData('is_autogenerated')
                && !$this->configProvider->isUpdateRewrites()
            ) {
                $this->printStatus(
                    $output,
                    '<comment>SEO Rewrite for this URL already exist. Skipped.</comment>'
                    . ' For re-generating rewrite remove the existing one first. Rewrite ID: '
                    . $seoRewrite->getId()
                );
                continue;
            }

            foreach (array_keys($meta) as $metaField) {
                if ($this->getFailedCheckByType($url, $metaField)) {
                    $fieldLabel = $this->promptService->fieldToLabel($metaField);

                    $this->printStatus($output, $fieldLabel . ' issue is found.');

                    $prompt = $this->promptService->preparePrompt(
                        $url,
                        $metaField,
                        $this->configProvider->getLanguage($storeId)
                    );

                    if (!$prompt) {
                        $this->printStatus(
                            $output,
                            '<comment>No context for this URL found. Skipped.</comment>' .
                            ' If you\'d like to generate meta fields for this URL with store context'
                            . ' - enable "Add store information to context"'
                        );

                        continue 2;
                    }

                    $meta[$metaField] = $this->makeRequest($prompt, $output);

                    $this->printStatus(
                        $output,
                        '<info>Generated ' . $fieldLabel . ':</info> ' . $meta[$metaField]
                    );
                }
            }

            if (!count(array_filter(array_values($meta)))) {
                continue;
            }

            if (!$seoRewrite || !$seoRewrite->getId()) {
                $seoRewrite = $this->seoRewriteRepository->create();
            }

            $seoRewrite->setStoreIds([$storeId])
                ->setIsActive(true)
                ->setSortOrder(100)
                ->setUrl($relativeUrl)
                ->setData('is_autogenerated', true);

            if ($meta['meta_title']) {
                $seoRewrite->setMetaTitle($meta['meta_title']);
            }

            if ($meta['meta_description']) {
                $seoRewrite->setMetaDescription($meta['meta_description']);
            }

            $this->seoRewriteRepository->save($seoRewrite);
            $this->printStatus($output, '<info>SEO Rewrite added.</info>');
        }
    }

    private function printStatus(?OutputInterface $output, string $message)
    {
        if ($output) {
            $output->writeln($message);
        }
    }

    private function resolveStoreId(string $url): ?int
    {
        $storeId = null;

        foreach ($this->storeManager->getStores() as $store) {
            $secureUrl = rtrim($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true), '/');
            $unsecureUrl = rtrim($store->getBaseUrl(), '/');

            if (strpos($url, $secureUrl) !== false || strpos($url, $unsecureUrl) !== false) {
                $storeId = (int)$store->getId();
                break;
            }
        }

        return $storeId;
    }

    private function getFailedCheckByType(UrlInterface $url, string $type): ?CheckResultInterface
    {
        $check = $this->checkResultRepository->getCollection()
            ->addFieldToFilter('url_id', $url->getId())
            ->addFieldToFilter('identifier', ['like' => $type . '_%'])
            ->addFieldToFilter('job_id', $url->getJobId())
            ->addFieldToFilter('result', ['lt' => 10])
            ->getLastItem();

        return $check && $check->getId() ? $check : null;
    }

    private function makeRequest(string $prompt, ?OutputInterface $output): string
    {
        try {
            $answer = $this->completionsService->answer($prompt);
            $this->attempt = 0;

            return $answer;
        } catch (\Exception $e) {
            if ($this->attempt == self::MAX_ATTEMPTS) {
                $this->attempt = 0;
                $this->printStatus($output, '<error>Max errors limit reached. Skipped</error>');
                return '';
            }

            $this->attempt++;

            $this->printStatus($output, '<comment>Error encountered:</comment> ' . $e->getMessage());
            $this->printStatus($output, 'Retrying in: ' . (self::DELAY * $this->attempt) . ' seconds');

            sleep(self::DELAY * $this->attempt);

            return $this->makeRequest($prompt, $output);
        }
    }

    public function reset()
    {
        $autogeneratedRewrites = $this->seoRewriteRepository->getCollection()
            ->addFieldToFilter('is_autogenerated', 1);

        foreach ($autogeneratedRewrites as $rewrite) {
            $this->seoRewriteRepository->delete($rewrite);
        }
    }
}
