<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Element\UiComponent\Config\Provider;

use Magento\Framework\View\Element\UiComponent\Config\FileCollector\AggregatedFileCollector;
use Magento\Framework\View\Element\UiComponent\Config\FileCollector\AggregatedFileCollectorFactory;

/**
 * Class Template
 */
class Template
{
    /**
     * Components node name in config
     */
    const TEMPLATE_KEY = 'template';

    /**
     * ID in the storage cache
     */
    const CACHE_ID = 'ui_component_templates';

    /**
     * @var AggregatedFileCollector
     */
    protected $aggregatedFileCollector;

    /**
     * @var \Magento\Framework\View\Element\UiComponent\Config\DomMergerInterface
     */
    protected $domMerger;

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $cache;

    /**
     * Factory for UI config reader
     *
     * @var \Magento\Framework\View\Element\UiComponent\Config\ReaderFactory
     */
    protected $readerFactory;

    /**
     * @var AggregatedFileCollectorFactory
     */
    protected $aggregatedFileCollectorFactory;

    /**
     * @var array
     */
    protected $cachedTemplates = [];

    /**
     * @var \Magento\Framework\Json\JsonInterface
     */
    private $json;

    /**
     * Constructor
     *
     * @param AggregatedFileCollector $aggregatedFileCollector
     * @param \Magento\Framework\View\Element\UiComponent\Config\DomMergerInterface $domMerger
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Magento\Framework\View\Element\UiComponent\Config\ReaderFactory $readerFactory
     * @param AggregatedFileCollectorFactory $aggregatedFileCollectorFactory
     */
    public function __construct(
        AggregatedFileCollector $aggregatedFileCollector,
        \Magento\Framework\View\Element\UiComponent\Config\DomMergerInterface $domMerger,
        \Magento\Framework\Config\CacheInterface $cache,
        \Magento\Framework\View\Element\UiComponent\Config\ReaderFactory $readerFactory,
        AggregatedFileCollectorFactory $aggregatedFileCollectorFactory
    ) {
        $this->aggregatedFileCollector = $aggregatedFileCollector;
        $this->domMerger = $domMerger;
        $this->cache = $cache;
        $this->readerFactory = $readerFactory;
        $this->aggregatedFileCollectorFactory = $aggregatedFileCollectorFactory;

        $cachedTemplates = $this->cache->load(static::CACHE_ID);
        $this->cachedTemplates = $cachedTemplates === false ? [] : $this->getJson()->decode($cachedTemplates);
    }

    /**
     * Get template content
     *
     * @param string $template
     * @return string
     * @throws \Exception
     */
    public function getTemplate($template)
    {
        $hash = sprintf('%x', crc32($template));
        if (isset($this->cachedTemplates[$hash])) {
            return $this->cachedTemplates[$hash];
        }
        $this->domMerger->unsetDom();
        $this->cachedTemplates[$hash] = $this->readerFactory->create(
            [
                'fileCollector' => $this->aggregatedFileCollectorFactory->create(['searchPattern' => $template]),
                'domMerger' => $this->domMerger
            ]
        )->getContent();
        $this->cache->save($this->getJson()->encode($this->cachedTemplates), static::CACHE_ID);

        return $this->cachedTemplates[$hash];
    }

    /**
     * Get json encoder/decoder
     *
     * @return \Magento\Framework\Json\JsonInterface
     * @deprecated
     */
    private function getJson()
    {
        if ($this->json === null) {
            $this->json = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Json\JsonInterface::class);
        }
        return $this->json;
    }
}
