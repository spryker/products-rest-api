<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\AbstractProductsStorefrontResource;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper\AbstractProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Reader\AbstractProductsAttributesReaderInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;
use Spryker\Service\Serializer\SerializerServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class AbstractProductsStorefrontProvider extends AbstractStorefrontProvider
{
    public const string CONTEXT_KEY_ABSTRACT_PRODUCT_IDS = 'abstractProductIds';

    protected const string KEY_SKU = 'sku';

    public function __construct(
        protected AbstractProductsAttributesReaderInterface $abstractProductsAttributesReader,
        protected AbstractProductsResourceMapperInterface $abstractProductsResourceMapper,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return array<\Generated\Api\Storefront\AbstractProductsStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $abstractProductIds = $this->context[static::CONTEXT_KEY_ABSTRACT_PRODUCT_IDS] ?? null;

        if (is_array($abstractProductIds) && $abstractProductIds !== []) {
            return $this->buildResourcesByAbstractProductIds($abstractProductIds);
        }

        throw new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            ProductsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
            ProductsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
        );
    }

    /**
     * @param array<int> $abstractProductIds
     *
     * @return array<\Generated\Api\Storefront\AbstractProductsStorefrontResource>
     */
    protected function buildResourcesByAbstractProductIds(array $abstractProductIds): array
    {
        $locale = $this->getLocale()->getLocaleNameOrFail();

        $transfers = $this->abstractProductsAttributesReader->findBulkAbstractProductAttributesByIds(
            $abstractProductIds,
            $locale,
            $this->getStore()->getNameOrFail(),
        );

        $resources = [];

        foreach ($abstractProductIds as $abstractProductId) {
            if (!isset($transfers[$abstractProductId])) {
                continue;
            }

            $resources[] = $this->serializer->denormalize(
                $this->abstractProductsResourceMapper->mapAbstractProductsAttributesTransferToResourceData($transfers[$abstractProductId]),
                AbstractProductsStorefrontResource::class,
            );
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
                ProductsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
                ProductsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
            );
        }

        $sku = (string)$this->getUriVariable(static::KEY_SKU);

        if ($sku === '') {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                ProductsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
                ProductsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
            );
        }

        $transfer = $this->abstractProductsAttributesReader->findAbstractProductAttributes(
            $sku,
            $this->getLocale()->getLocaleNameOrFail(),
        );

        if ($transfer === null) {
            throw new GlueApiException(
                Response::HTTP_NOT_FOUND,
                ProductsRestApiConfig::RESPONSE_CODE_CANT_FIND_ABSTRACT_PRODUCT,
                ProductsRestApiConfig::RESPONSE_DETAIL_CANT_FIND_ABSTRACT_PRODUCT,
            );
        }

        return $this->serializer->denormalize(
            $this->abstractProductsResourceMapper->mapAbstractProductsAttributesTransferToResourceData($transfer),
            AbstractProductsStorefrontResource::class,
        );
    }
}
