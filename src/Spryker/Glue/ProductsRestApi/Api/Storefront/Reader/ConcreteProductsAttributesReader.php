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

        $transfer = (new ConcreteProductsRestAttributesTransfer())->fromArray($productConcreteData, true);
        $transfer = $this->enrichProductAbstractSku($transfer, $productConcreteData, $localeName);
        $transfer = $this->expandWithPlugins($transfer, $productConcreteData, $localeName);

        return $this->addAttributeTranslations($transfer, $localeName);
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
