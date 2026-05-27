<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Reader;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResource;
use Spryker\Glue\GlueApplication\Rest\Request\Data\Metadata;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequest;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;
use Spryker\Service\Container\Attributes\Plugins;
use Symfony\Component\HttpFoundation\Request;

class ConcreteProductsAttributesReader implements ConcreteProductsAttributesReaderInterface
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string KEY_SKU = 'sku';

    protected const string GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX = 'product.attribute.';

    /**
     * @param array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\ConcreteProductsResourceExpanderPluginInterface> $concreteProductsResourceExpanderPlugins
     */
    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected GlossaryStorageClientInterface $glossaryStorageClient,
        #[Plugins(dependencyProviderMethod: 'getConcreteProductsResourceExpanderPlugins')]
        protected array $concreteProductsResourceExpanderPlugins = [],
    ) {
    }

    /**
     * @param array<int> $concreteProductIds
     *
     * @return array<int, \Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer>
     */
    public function findBulkConcreteProductAttributesByIds(array $concreteProductIds, string $localeName): array
    {
        $bulkProductData = $this->productStorageClient->getBulkProductConcreteStorageData($concreteProductIds, $localeName);

        if ($bulkProductData === []) {
            return [];
        }

        $indexedProductData = [];

        foreach ($bulkProductData as $productData) {
            $idProductConcrete = $productData[static::KEY_ID_PRODUCT_CONCRETE] ?? null;

            if ($idProductConcrete === null) {
                continue;
            }

            $indexedProductData[(int)$idProductConcrete] = $productData;
        }

        $transfers = [];

        foreach ($concreteProductIds as $concreteProductId) {
            if (!isset($indexedProductData[$concreteProductId])) {
                continue;
            }

            $transfers[$concreteProductId] = $this->mapStorageDataToTransfer($indexedProductData[$concreteProductId], $localeName);
        }

        return $this->addBulkAttributeTranslations($transfers, $localeName);
    }

    /**
     * @param array<string> $skus
     *
     * @return array<string, \Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer>
     */
    public function getBulkConcreteProductAttributesBySkus(array $skus, string $localeName): array
    {
        if ($skus === []) {
            return [];
        }

        $bulkProductData = $this->productStorageClient->getBulkProductConcreteStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $skus,
            $localeName,
        );

        $concreteProductsRestAttributesTransfersIndexedBySku = [];

        foreach ($bulkProductData as $productData) {
            $sku = $productData[static::KEY_SKU] ?? null;

            if ($sku === null) {
                continue;
            }

            $transfer = $this->mapStorageDataToTransfer($productData, $localeName);
            $concreteProductsRestAttributesTransfersIndexedBySku[(string)$sku] = $this->addAttributeTranslations($transfer, $localeName);
        }

        return $concreteProductsRestAttributesTransfersIndexedBySku;
    }

    public function findConcreteProductAttributes(string $sku, string $localeName): ?ConcreteProductsRestAttributesTransfer
    {
        $productConcreteData = $this->productStorageClient->findProductConcreteStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );

        if ($productConcreteData === null) {
            return null;
        }

        return $this->addAttributeTranslations($this->mapStorageDataToTransfer($productConcreteData, $localeName), $localeName);
    }

    /**
     * @param array<string, mixed> $productConcreteData
     */
    protected function mapStorageDataToTransfer(array $productConcreteData, string $localeName): ConcreteProductsRestAttributesTransfer
    {
        $transfer = (new ConcreteProductsRestAttributesTransfer())->fromArray($productConcreteData, true);
        $transfer = $this->enrichProductAbstractSku($transfer, $productConcreteData, $localeName);

        return $this->expandWithPlugins($transfer, $productConcreteData, $localeName);
    }

    /**
     * Invokes legacy `ConcreteProductsResourceExpanderPluginInterface` implementations (reviews,
     * labels, discontinued, configurations, multi-select attributes, etc.) via a fabricated
     * RestRequest carrying only the locale. All current core implementations of this plugin
     * interface read `$restRequest->getMetadata()->getLocale()` at most.
     *
     * @param array<string, mixed> $productConcreteData
     */
    protected function expandWithPlugins(
        ConcreteProductsRestAttributesTransfer $transfer,
        array $productConcreteData,
        string $localeName
    ): ConcreteProductsRestAttributesTransfer {
        $idProductConcrete = (int)($productConcreteData[static::KEY_ID_PRODUCT_CONCRETE] ?? 0);

        if ($idProductConcrete === 0) {
            return $transfer;
        }

        $restRequest = $this->buildMinimalRestRequest($localeName);

        foreach ($this->concreteProductsResourceExpanderPlugins as $plugin) {
            $transfer = $plugin->expand($transfer, $idProductConcrete, $restRequest);
        }

        return $transfer;
    }

    protected function buildMinimalRestRequest(string $localeName): RestRequestInterface
    {
        $metadata = new Metadata(
            acceptFormat: '',
            contentTypeFormat: '',
            method: Request::METHOD_GET,
            locale: $localeName,
            isProtected: false,
        );

        return new RestRequest(
            resource: new RestResource(ProductsRestApiConfig::RESOURCE_CONCRETE_PRODUCTS),
            httpRequest: new Request(),
            metadata: $metadata,
            filters: [],
            sort: [],
            page: null,
            routeContext: [],
            parentResources: [],
            include: [],
            fields: [],
            excludeRelationship: false,
        );
    }

    /**
     * Concrete storage data carries `id_product_abstract` but not `product_abstract_sku`.
     * Resolve the abstract SKU by loading the abstract storage record by id.
     *
     * @param array<string, mixed> $productConcreteData
     */
    protected function enrichProductAbstractSku(
        ConcreteProductsRestAttributesTransfer $transfer,
        array $productConcreteData,
        string $localeName
    ): ConcreteProductsRestAttributesTransfer {
        if ($transfer->getProductAbstractSku() !== null) {
            return $transfer;
        }

        $idProductAbstract = $productConcreteData[static::KEY_ID_PRODUCT_ABSTRACT] ?? null;

        if ($idProductAbstract === null) {
            return $transfer;
        }

        $abstractData = $this->productStorageClient->findProductAbstractStorageData(
            (int)$idProductAbstract,
            $localeName,
        );

        if ($abstractData === null) {
            return $transfer;
        }

        return $transfer->setProductAbstractSku($abstractData[static::KEY_SKU] ?? null);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer> $transfers
     *
     * @return array<int, \Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer>
     */
    protected function addBulkAttributeTranslations(array $transfers, string $localeName): array
    {
        $glossaryKeyToAttributeKey = [];

        foreach ($transfers as $transfer) {
            foreach (array_keys($transfer->getAttributes()) as $attributeKey) {
                $glossaryKey = static::GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX . $attributeKey;
                $glossaryKeyToAttributeKey[$glossaryKey] = $attributeKey;
            }
        }

        if ($glossaryKeyToAttributeKey === []) {
            return $transfers;
        }

        $translations = $this->glossaryStorageClient->translateBulk(array_keys($glossaryKeyToAttributeKey), $localeName);

        foreach ($transfers as $id => $transfer) {
            $attributeNames = [];

            foreach (array_keys($transfer->getAttributes()) as $attributeKey) {
                $glossaryKey = static::GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX . $attributeKey;
                $attributeNames[$attributeKey] = $translations[$glossaryKey] ?? $attributeKey;
            }

            $transfers[$id] = $transfer->setAttributeNames($attributeNames);
        }

        return $transfers;
    }

    protected function addAttributeTranslations(
        ConcreteProductsRestAttributesTransfer $transfer,
        string $localeName
    ): ConcreteProductsRestAttributesTransfer {
        $attributeNames = [];

        foreach ($transfer->getAttributes() as $key => $value) {
            $attributeNames[$key] = $this->glossaryStorageClient->translate(
                static::GLOSSARY_PRODUCT_ATTRIBUTE_NAME_KEY_PREFIX . $key,
                $localeName,
            );
        }

        return $transfer->setAttributeNames($attributeNames);
    }
}
