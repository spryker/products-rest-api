<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\ConcreteProductsStorefrontResource;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper\ConcreteProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Reader\ConcreteProductsAttributesReaderInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;
use Spryker\Service\Serializer\SerializerServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class ConcreteProductsStorefrontProvider extends AbstractStorefrontProvider
{
    public const string CONTEXT_KEY_CONCRETE_PRODUCT_IDS = 'concreteProductIds';

    protected const string KEY_SKU = 'sku';

    public function __construct(
        protected ConcreteProductsAttributesReaderInterface $concreteProductsAttributesReader,
        protected ConcreteProductsResourceMapperInterface $concreteProductsResourceMapper,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return array<\Generated\Api\Storefront\ConcreteProductsStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $concreteProductIds = $this->context[static::CONTEXT_KEY_CONCRETE_PRODUCT_IDS] ?? null;

        if (is_array($concreteProductIds) && $concreteProductIds !== []) {
            return $this->buildResourcesByConcreteProductIds($concreteProductIds);
        }

        throw new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            ProductsRestApiConfig::RESPONSE_CODE_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
            ProductsRestApiConfig::RESPONSE_DETAIL_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
        );
    }

    /**
     * @param array<int> $concreteProductIds
     *
     * @return array<\Generated\Api\Storefront\ConcreteProductsStorefrontResource>
     */
    protected function buildResourcesByConcreteProductIds(array $concreteProductIds): array
    {
        $transfers = $this->concreteProductsAttributesReader->findBulkConcreteProductAttributesByIds(
            $concreteProductIds,
            $this->getLocale()->getLocaleNameOrFail(),
        );

        $resources = [];

        foreach ($concreteProductIds as $concreteProductId) {
            if (!isset($transfers[$concreteProductId])) {
                continue;
            }

            $resources[] = $this->denormalizeToResource($transfers[$concreteProductId]);
        }

        return $resources;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function provideItem(): ?object
    {
        if (!$this->hasUriVariable(static::KEY_SKU)) {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                ProductsRestApiConfig::RESPONSE_CODE_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
                ProductsRestApiConfig::RESPONSE_DETAIL_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
            );
        }

        $sku = (string)$this->getUriVariable(static::KEY_SKU);

        if ($sku === '') {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                ProductsRestApiConfig::RESPONSE_CODE_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
                ProductsRestApiConfig::RESPONSE_DETAIL_CONCRETE_PRODUCT_SKU_IS_NOT_SPECIFIED,
            );
        }

        $transfer = $this->concreteProductsAttributesReader->findConcreteProductAttributes(
            $sku,
            $this->getLocale()->getLocaleNameOrFail(),
        );

        if ($transfer === null) {
            throw new GlueApiException(
                Response::HTTP_NOT_FOUND,
                ProductsRestApiConfig::RESPONSE_CODE_CANT_FIND_CONCRETE_PRODUCT,
                ProductsRestApiConfig::RESPONSE_DETAIL_CANT_FIND_CONCRETE_PRODUCT,
            );
        }

        return $this->denormalizeToResource($transfer);
    }

    protected function denormalizeToResource(mixed $transfer): ConcreteProductsStorefrontResource
    {
        return $this->serializer->denormalize(
            $this->concreteProductsResourceMapper->mapConcreteProductsRestAttributesTransferToResourceData($transfer),
            ConcreteProductsStorefrontResource::class,
        );
    }
}
