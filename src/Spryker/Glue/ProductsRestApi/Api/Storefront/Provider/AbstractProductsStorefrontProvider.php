<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\AbstractProductsStorefrontResource;
use Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Glue\ProductsRestApi\Api\Storefront\Reader\AbstractProductsAttributesReaderInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class AbstractProductsStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string KEY_SKU = 'sku';

    public function __construct(
        protected AbstractProductsAttributesReaderInterface $abstractProductsAttributesReader,
    ) {
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function provideCollection(): array
    {
        throw new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            ProductsRestApiConfig::RESPONSE_CODE_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
            ProductsRestApiConfig::RESPONSE_DETAIL_ABSTRACT_PRODUCT_SKU_IS_NOT_SPECIFIED,
        );
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

        return $this->mapTransferToResource($transfer);
    }

    protected function mapTransferToResource(
        AbstractProductsRestAttributesTransfer $transfer
    ): AbstractProductsStorefrontResource {
        $resource = new AbstractProductsStorefrontResource();
        $resource->sku = $transfer->getSku();
        $resource->name = $transfer->getName();
        $resource->description = $transfer->getDescription();
        $resource->attributes = $transfer->getAttributes();
        $resource->superAttributes = $transfer->getSuperAttributes();
        $resource->superAttributesDefinition = $transfer->getSuperAttributesDefinition();
        $resource->attributeMap = $transfer->getAttributeMap();
        $resource->concreteProductSkus = array_values($resource->attributeMap['product_concrete_ids'] ?? []);
        $resource->metaTitle = $transfer->getMetaTitle();
        $resource->metaKeywords = $transfer->getMetaKeywords();
        $resource->metaDescription = $transfer->getMetaDescription();
        $resource->attributeNames = $transfer->getAttributeNames();
        $resource->url = $transfer->getUrl();
        $resource->merchantReference = $transfer->getMerchantReference();
        $resource->averageRating = $transfer->getAverageRating();
        $resource->reviewCount = $transfer->getReviewCount() ?? 0;

        return $resource;
    }
}
