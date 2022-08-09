<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsRestApi\Processor\Expander;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;
use Generated\Shared\Transfer\RestPromotionalItemsAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReaderInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;

class ProductAbstractRelationshipExpander implements ProductAbstractRelationshipExpanderInterface
{
    /**
     * @var string
     */
    protected const KEY_SKU = 'sku';

    /**
     * @var \Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReaderInterface
     */
    protected $abstractProductsReader;

    /**
     * @param \Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReaderInterface $abstractProductsReader
     */
    public function __construct(AbstractProductsReaderInterface $abstractProductsReader)
    {
        $this->abstractProductsReader = $abstractProductsReader;
    }

    /**
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return void
     */
    public function addResourceRelationshipsBySkuList(array $resources, RestRequestInterface $restRequest): void
    {
        $productAbstractSkuList = $this->getProductAbstractSkuList($resources);
        if (!$productAbstractSkuList) {
            return;
        }

        $abstractProductsResources = $this->abstractProductsReader->getProductAbstractsBySkus($productAbstractSkuList, $restRequest);
        if (!$abstractProductsResources) {
            return;
        }

        foreach ($resources as $resource) {
            $productAbstractSku = $this->findProductAbstractSkuInRestResourceAttributes($resource);
            if (!isset($abstractProductsResources[$productAbstractSku])) {
                continue;
            }

            $resource->addRelationship($abstractProductsResources[$productAbstractSku]);
        }
    }

    /**
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return void
     */
    public function addResourceRelationships(array $resources, RestRequestInterface $restRequest): void
    {
        $productAbstractSkus = $this->getProductAbstractSkus($resources);
        if (!$productAbstractSkus) {
            return;
        }

        if ($this->isAbstractProductsResource($restRequest->getResource())) {
            return;
        }

        $abstractProductsResources = $this->abstractProductsReader
            ->getProductAbstractsBySkus($productAbstractSkus, $restRequest);
        if (!$abstractProductsResources) {
            return;
        }

        foreach ($resources as $resource) {
            $productAbstractSku = $this->findProductAbstractSkuInAttributes($resource);
            if (!$productAbstractSku || !isset($abstractProductsResources[$productAbstractSku])) {
                continue;
            }

            $resource->addRelationship($abstractProductsResources[$productAbstractSku]);
        }
    }

    /**
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     *
     * @return array<string>
     */
    protected function getProductAbstractSkuList(array $resources): array
    {
        $productAbstractSkuList = [];
        foreach ($resources as $resource) {
            $productAbstractSku = $this->findProductAbstractSkuInRestResourceAttributes($resource);
            if (!$productAbstractSku) {
                continue;
            }

            $productAbstractSkuList[] = $productAbstractSku;
        }

        return $productAbstractSkuList;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface $resource
     *
     * @return string|null
     */
    protected function findProductAbstractSkuInRestResourceAttributes(RestResourceInterface $resource): ?string
    {
        $attributes = $resource->getAttributes();
        if ($attributes && $attributes instanceof RestPromotionalItemsAttributesTransfer) {
            return $attributes->offsetGet(static::KEY_SKU);
        }

        return null;
    }

    /**
     * @param array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface> $resources
     *
     * @return array<string>
     */
    protected function getProductAbstractSkus(array $resources): array
    {
        $productAbstractSkuList = [];
        foreach ($resources as $resource) {
            $productAbstractSkuList[] = $this->findProductAbstractSkuInAttributes($resource);
        }

        return array_unique(array_filter($productAbstractSkuList));
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface $resource
     *
     * @return string|null
     */
    protected function findProductAbstractSkuInAttributes(RestResourceInterface $resource): ?string
    {
        $attributes = $resource->getAttributes();
        if ($attributes && $attributes->offsetExists(ConcreteProductsRestAttributesTransfer::PRODUCT_ABSTRACT_SKU)) {
            return $attributes->offsetGet(ConcreteProductsRestAttributesTransfer::PRODUCT_ABSTRACT_SKU);
        }

        return null;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface $resource
     *
     * @return bool
     */
    protected function isAbstractProductsResource(RestResourceInterface $resource): bool
    {
        return $resource->getType() === ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS;
    }
}
