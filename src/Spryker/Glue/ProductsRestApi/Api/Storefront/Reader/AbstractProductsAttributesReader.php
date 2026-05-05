<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Reader;

use Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Service\Container\Attributes\Plugins;

class AbstractProductsAttributesReader implements AbstractProductsAttributesReaderInterface
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string KEY_SUPER_ATTRIBUTES = 'super_attributes';

    protected const string KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    protected const string KEY_ATTRIBUTE_VARIANTS = 'attribute_variants';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX = 'product.attribute.';

    /**
     * @param array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\AbstractProductsResourceExpanderPluginInterface> $abstractProductsResourceExpanderPlugins
     */
    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected GlossaryStorageClientInterface $glossaryStorageClient,
        #[Plugins(dependencyProviderMethod: 'getAbstractProductsResourceExpanderPlugins')]
        protected array $abstractProductsResourceExpanderPlugins = [],
    ) {
    }

    public function findAbstractProductAttributes(string $sku, string $localeName): ?AbstractProductsRestAttributesTransfer
    {
        $productAbstractData = $this->productStorageClient->findProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );

        if ($productAbstractData === null) {
            return null;
        }

        $transfer = $this->mapStorageDataToTransfer($productAbstractData);
        $transfer = $this->expandWithPlugins($transfer, $productAbstractData, $localeName);

        $transfers = $this->addAttributeTranslations([$sku => $transfer], $localeName);

        return $transfers[$sku];
    }

    /**
     * @param array<string> $skus
     *
     * @return array<string, \Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer>
     */
    public function findBulkAbstractProductAttributes(array $skus, string $localeName): array
    {
        $bulkProductData = $this->productStorageClient->findBulkProductAbstractStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $skus,
            $localeName,
        );

        if ($bulkProductData === []) {
            return [];
        }

        $transfers = [];

        foreach ($bulkProductData as $productAbstractData) {
            $sku = $productAbstractData[static::KEY_SKU] ?? null;

            if (!is_string($sku) || $sku === '') {
                continue;
            }

            $transfer = $this->mapStorageDataToTransfer($productAbstractData);
            $transfer = $this->expandWithPlugins($transfer, $productAbstractData, $localeName);
            $transfers[$sku] = $transfer;
        }

        return $this->addAttributeTranslations($transfers, $localeName);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer> $abstractProductsRestAttributesTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer>
     */
    protected function addAttributeTranslations(array $abstractProductsRestAttributesTransfers, string $localeName): array
    {
        $attributeKeysIndexedByGlossaryKey = [];
        $attributeKeysIndexedBySku = [];

        foreach ($abstractProductsRestAttributesTransfers as $sku => $abstractProductsRestAttributesTransfer) {
            $attributeKeys = array_keys(array_merge($abstractProductsRestAttributesTransfer->getAttributes(), $abstractProductsRestAttributesTransfer->getSuperAttributes()));
            $attributeKeysIndexedBySku[$sku] = $attributeKeys;

            foreach ($attributeKeys as $key) {
                $glossaryKey = strtolower(static::GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX . $key);
                $attributeKeysIndexedByGlossaryKey[$glossaryKey] = $key;
            }
        }

        if ($attributeKeysIndexedByGlossaryKey === []) {
            return $abstractProductsRestAttributesTransfers;
        }

        $translations = $this->glossaryStorageClient->translateBulk(
            array_keys($attributeKeysIndexedByGlossaryKey),
            $localeName,
        );

        $attributeTranslations = [];

        foreach ($attributeKeysIndexedByGlossaryKey as $glossaryKey => $attributeKey) {
            $attributeTranslations[$attributeKey] = $translations[$glossaryKey] ?? $attributeKey;
        }

        foreach ($abstractProductsRestAttributesTransfers as $sku => $abstractProductsRestAttributesTransfer) {
            $attributeNamesIndexedByKey = [];

            foreach ($attributeKeysIndexedBySku[$sku] as $key) {
                $attributeNamesIndexedByKey[$key] = $attributeTranslations[$key] ?? $key;
            }

            $abstractProductsRestAttributesTransfers[$sku] = $abstractProductsRestAttributesTransfer->setAttributeNames($attributeNamesIndexedByKey);
        }

        return $abstractProductsRestAttributesTransfers;
    }

    /**
     * @param array<string, mixed> $productAbstractData
     */
    protected function mapStorageDataToTransfer(array $productAbstractData): AbstractProductsRestAttributesTransfer
    {
        $transfer = (new AbstractProductsRestAttributesTransfer())->fromArray($productAbstractData, true);
        $attributeMap = $transfer->getAttributeMap();
        $transfer->setSuperAttributes($attributeMap[static::KEY_SUPER_ATTRIBUTES] ?? []);

        return $this->replaceConcreteIdsWithSkus($transfer);
    }

    protected function replaceConcreteIdsWithSkus(
        AbstractProductsRestAttributesTransfer $transfer
    ): AbstractProductsRestAttributesTransfer {
        $attributeMap = $transfer->getAttributeMap();

        if (!isset($attributeMap[static::KEY_PRODUCT_CONCRETE_IDS])) {
            return $transfer;
        }

        $productConcreteIdsBySku = array_flip($attributeMap[static::KEY_PRODUCT_CONCRETE_IDS]);

        if (isset($attributeMap[static::KEY_ATTRIBUTE_VARIANTS])) {
            $attributeMap[static::KEY_ATTRIBUTE_VARIANTS] = $this->replaceVariantIdsWithSkus(
                $attributeMap[static::KEY_ATTRIBUTE_VARIANTS],
                $productConcreteIdsBySku,
            );
        }

        $attributeMap[static::KEY_PRODUCT_CONCRETE_IDS] = array_values($productConcreteIdsBySku);

        return $transfer->setAttributeMap($attributeMap);
    }

    /**
     * @param array<string, mixed> $variants
     * @param array<int, string> $productConcreteIdsBySku
     *
     * @return array<string, mixed>
     */
    protected function replaceVariantIdsWithSkus(array $variants, array $productConcreteIdsBySku): array
    {
        foreach ($variants as $key => $variant) {
            if (isset($variant[static::KEY_ID_PRODUCT_CONCRETE])) {
                $variants[$key][static::KEY_ID_PRODUCT_CONCRETE] = $productConcreteIdsBySku[$variant[static::KEY_ID_PRODUCT_CONCRETE]];

                continue;
            }

            $variants[$key] = $this->replaceVariantIdsWithSkus($variant, $productConcreteIdsBySku);
        }

        return $variants;
    }

    /**
     * @param array<string, mixed> $productAbstractData
     */
    protected function expandWithPlugins(
        AbstractProductsRestAttributesTransfer $transfer,
        array $productAbstractData,
        string $localeName
    ): AbstractProductsRestAttributesTransfer {
        $idProductAbstract = (int)($productAbstractData[static::KEY_ID_PRODUCT_ABSTRACT] ?? 0);

        foreach ($this->abstractProductsResourceExpanderPlugins as $plugin) {
            $transfer = $plugin->expand($transfer, $idProductAbstract, $localeName);
        }

        return $transfer;
    }
}
