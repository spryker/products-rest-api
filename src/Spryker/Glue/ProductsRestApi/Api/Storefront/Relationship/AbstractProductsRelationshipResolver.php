<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Relationship;

use Generated\Api\Storefront\AbstractProductsStorefrontResource;
use Spryker\ApiPlatform\Relationship\AbstractRelationshipResolver;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper\AbstractProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Reader\AbstractProductsAttributesReaderInterface;
use Spryker\Service\Serializer\SerializerServiceInterface;

class AbstractProductsRelationshipResolver extends AbstractRelationshipResolver
{
    protected const string KEY_ABSTRACT_SKU = 'abstractSku';

    protected const string DEFAULT_ABSTRACT_PRODUCTS_FIELD = 'abstractProducts';

    public function __construct(
        protected AbstractProductsAttributesReaderInterface $abstractProductsAttributesReader,
        protected AbstractProductsResourceMapperInterface $abstractProductsResourceMapper,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\AbstractProductsStorefrontResource>
     */
    protected function resolveRelationship(): array
    {
        $abstractSkus = $this->extractAbstractProductSkus($this->getParentResources());

        if ($abstractSkus === []) {
            return [];
        }

        $locale = $this->getLocale()->getLocaleName() ?? '';
        $transfersBySkus = $this->abstractProductsAttributesReader->findBulkAbstractProductAttributes(
            $abstractSkus,
            $locale,
        );

        $resources = [];

        foreach ($transfersBySkus as $transfer) {
            $resources[] = $this->serializer->denormalize(
                $this->abstractProductsResourceMapper->mapAbstractProductsAttributesTransferToResourceData($transfer),
                AbstractProductsStorefrontResource::class,
            );
        }

        return $resources;
    }

    /**
     * @param array<object> $parentResources
     *
     * @return array<string>
     */
    protected function extractAbstractProductSkus(array $parentResources): array
    {
        $skus = [];

        foreach ($parentResources as $resource) {
            foreach ($this->extractSkusFromParentResource($resource) as $sku) {
                $skus[$sku] = $sku;
            }
        }

        return array_values($skus);
    }

    /**
     * @return array<string>
     */
    protected function extractSkusFromParentResource(object $resource): array
    {
        $abstractProducts = $resource->{static::DEFAULT_ABSTRACT_PRODUCTS_FIELD} ?? [];

        if (!is_array($abstractProducts)) {
            return [];
        }

        $skus = [];

        foreach ($abstractProducts as $product) {
            $sku = is_array($product)
                ? ($product[static::KEY_ABSTRACT_SKU] ?? null)
                : ($product->{static::KEY_ABSTRACT_SKU} ?? null);

            if (!is_string($sku) || $sku === '') {
                continue;
            }

            $skus[] = $sku;
        }

        return $skus;
    }
}
