<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsRestApi;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToGlossaryStorageClientInterface;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToStoreClientInterface;
use Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReader;
use Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReaderInterface;
use Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\Storage\ProductAbstractRestUrlResolverAttributesReader;
use Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\Storage\ProductAbstractRestUrlResolverAttributesReaderInterface;
use Spryker\Glue\ProductsRestApi\Processor\ConcreteProducts\ConcreteProductsReader;
use Spryker\Glue\ProductsRestApi\Processor\ConcreteProducts\ConcreteProductsReaderInterface;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsByProductConcreteIdsResourceRelationshipExpander;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsByQuoteRequestResourceRelationshipExpander;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsBySkuResourceRelationshipExpander;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsResourceRelationshipExpanderInterface;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ProductAbstractRelationshipExpander;
use Spryker\Glue\ProductsRestApi\Processor\Expander\ProductAbstractRelationshipExpanderInterface;
use Spryker\Glue\ProductsRestApi\Processor\Mapper\AbstractProductsResourceMapper;
use Spryker\Glue\ProductsRestApi\Processor\Mapper\AbstractProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Processor\Mapper\ConcreteProductsResourceMapper;
use Spryker\Glue\ProductsRestApi\Processor\Mapper\ConcreteProductsResourceMapperInterface;
use Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\AbstractProductAttributeTranslationExpander;
use Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\AbstractProductAttributeTranslationExpanderInterface;
use Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\ConcreteProductAttributeTranslationExpander;
use Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\ConcreteProductAttributeTranslationExpanderInterface;

/**
 * @method \Spryker\Glue\ProductsRestApi\ProductsRestApiConfig getConfig()
 */
class ProductsRestApiFactory extends AbstractFactory
{
    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Mapper\AbstractProductsResourceMapperInterface
     */
    public function createAbstractProductsResourceMapper(): AbstractProductsResourceMapperInterface
    {
        return new AbstractProductsResourceMapper();
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\AbstractProductsReaderInterface
     */
    public function createAbstractProductsReader(): AbstractProductsReaderInterface
    {
        return new AbstractProductsReader(
            $this->getProductStorageClient(),
            $this->getResourceBuilder(),
            $this->createAbstractProductsResourceMapper(),
            $this->createConcreteProductsReader(),
            $this->getConfig(),
            $this->createAbstractProductAttributeTranslationExpander(),
            $this->getAbstractProductResourceExpanderPlugins(),
        );
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\Storage\ProductAbstractRestUrlResolverAttributesReaderInterface
     */
    public function createProductAbstractRestUrlResolverAttributesReader(): ProductAbstractRestUrlResolverAttributesReaderInterface
    {
        return new ProductAbstractRestUrlResolverAttributesReader(
            $this->getProductStorageClient(),
        );
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Mapper\ConcreteProductsResourceMapperInterface
     */
    public function createConcreteProductsResourceMapper(): ConcreteProductsResourceMapperInterface
    {
        return new ConcreteProductsResourceMapper();
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\ConcreteProducts\ConcreteProductsReaderInterface
     */
    public function createConcreteProductsReader(): ConcreteProductsReaderInterface
    {
        return new ConcreteProductsReader(
            $this->getProductStorageClient(),
            $this->getStoreClient(),
            $this->getResourceBuilder(),
            $this->createConcreteProductsResourceMapper(),
            $this->createConcreteProductAttributeTranslationExpander(),
            $this->getConcreteProductResourceExpanderPlugins(),
        );
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\AbstractProductAttributeTranslationExpanderInterface
     */
    public function createAbstractProductAttributeTranslationExpander(): AbstractProductAttributeTranslationExpanderInterface
    {
        return new AbstractProductAttributeTranslationExpander(
            $this->getGlossaryStorageClient(),
        );
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\ProductAttribute\ConcreteProductAttributeTranslationExpanderInterface
     */
    public function createConcreteProductAttributeTranslationExpander(): ConcreteProductAttributeTranslationExpanderInterface
    {
        return new ConcreteProductAttributeTranslationExpander(
            $this->getGlossaryStorageClient(),
        );
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface
     */
    public function getProductStorageClient(): ProductsRestApiToProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductsRestApiDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToGlossaryStorageClientInterface
     */
    public function getGlossaryStorageClient(): ProductsRestApiToGlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(ProductsRestApiDependencyProvider::CLIENT_GLOSSARY_STORAGE);
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToStoreClientInterface
     */
    public function getStoreClient(): ProductsRestApiToStoreClientInterface
    {
        return $this->getProvidedDependency(ProductsRestApiDependencyProvider::CLIENT_STORE);
    }

    /**
     * @return array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\ConcreteProductsResourceExpanderPluginInterface>
     */
    public function getConcreteProductResourceExpanderPlugins(): array
    {
        return $this->getProvidedDependency(ProductsRestApiDependencyProvider::PLUGINS_CONCRETE_PRODUCTS_RESOURCE_EXPANDER);
    }

    /**
     * @return array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\AbstractProductsResourceExpanderPluginInterface>
     */
    public function getAbstractProductResourceExpanderPlugins(): array
    {
        return $this->getProvidedDependency(ProductsRestApiDependencyProvider::PLUGINS_ABSTRACT_PRODUCTS_RESOURCE_EXPANDER);
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Expander\ProductAbstractRelationshipExpanderInterface
     */
    public function createProductAbstractRelationshipExpander(): ProductAbstractRelationshipExpanderInterface
    {
        return new ProductAbstractRelationshipExpander($this->createAbstractProductsReader());
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsResourceRelationshipExpanderInterface
     */
    public function createConcreteProductsByQuoteRequestResourceRelationshipExpander(): ConcreteProductsResourceRelationshipExpanderInterface
    {
        return new ConcreteProductsByQuoteRequestResourceRelationshipExpander($this->createConcreteProductsReader());
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsResourceRelationshipExpanderInterface
     */
    public function createConcreteProductsBySkuResourceRelationshipExpander(): ConcreteProductsResourceRelationshipExpanderInterface
    {
        return new ConcreteProductsBySkuResourceRelationshipExpander($this->createConcreteProductsReader());
    }

    /**
     * @return \Spryker\Glue\ProductsRestApi\Processor\Expander\ConcreteProductsResourceRelationshipExpanderInterface
     */
    public function createConcreteProductsByProductConcreteIdsResourceRelationshipExpander(): ConcreteProductsResourceRelationshipExpanderInterface
    {
        return new ConcreteProductsByProductConcreteIdsResourceRelationshipExpander($this->createConcreteProductsReader());
    }
}
