<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Relationship;

use Generated\Api\Storefront\ConcreteProductsStorefrontResource;
use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;
use Spryker\ApiPlatform\Relationship\AbstractRelationshipResolver;
use Spryker\ApiPlatform\Relationship\PerItemRelationshipResolverInterface;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper\ConcreteProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Reader\ConcreteProductsAttributesReaderInterface;
use Spryker\Service\Serializer\SerializerServiceInterface;

class ConcreteProductsRelationshipResolver extends AbstractRelationshipResolver implements PerItemRelationshipResolverInterface
{
    public function __construct(
        protected ConcreteProductsAttributesReaderInterface $concreteProductsAttributesReader,
        protected ConcreteProductsResourceMapperInterface $concreteProductsResourceMapper,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @param array<object> $parentResources
     * @param array<string, mixed> $context
     *
     * @return array<string, array<object>>
     */
    public function resolvePerItem(array $parentResources, array $context): array
    {
        $this->parentResources = $parentResources;
        $this->context = $context;
        $locale = $this->getLocale()->getLocaleName() ?? '';

        $skusIndexedByUuid = [];

        foreach ($parentResources as $parentResource) {
            $uuid = $parentResource->uuid ?? null;
            $sku = $parentResource->sku ?? null;

            if ($uuid === null || $sku === null) {
                continue;
            }

            $skusIndexedByUuid[$uuid] = $sku;
        }

        if ($skusIndexedByUuid === []) {
            return [];
        }

        $concreteProductsRestAttributesTransfersIndexedBySku = $this->concreteProductsAttributesReader->getBulkConcreteProductAttributesBySkus(
            array_values($skusIndexedByUuid),
            $locale,
        );

        $concreteProductResourcesIndexedByUuid = [];

        foreach ($skusIndexedByUuid as $uuid => $sku) {
            $concreteProductsRestAttributesTransfer = $concreteProductsRestAttributesTransfersIndexedBySku[$sku] ?? null;

            if ($concreteProductsRestAttributesTransfer === null) {
                $concreteProductResourcesIndexedByUuid[$uuid] = [];

                continue;
            }

            $concreteProductResourcesIndexedByUuid[$uuid] = [
                $this->mapTransferToResource($concreteProductsRestAttributesTransfer),
            ];
        }

        return $concreteProductResourcesIndexedByUuid;
    }

    protected function mapTransferToResource(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer,
    ): ConcreteProductsStorefrontResource {
        return $this->serializer->denormalize(
            $this->concreteProductsResourceMapper->mapConcreteProductsRestAttributesTransferToResourceData($concreteProductsRestAttributesTransfer),
            ConcreteProductsStorefrontResource::class,
        );
    }

    /**
     * @return array<object>
     */
    protected function resolveRelationship(): array
    {
        $allResources = [];

        foreach ($this->resolvePerItem($this->parentResources, $this->context) as $resources) {
            $allResources = array_merge($allResources, $resources);
        }

        return $allResources;
    }
}
