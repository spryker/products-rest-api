<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsRestApi;

use Spryker\Glue\Kernel\AbstractBundleDependencyProvider;
use Spryker\Glue\Kernel\Container;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToGlossaryStorageClientBridge;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientBridge;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToStoreClientBridge;

/**
 * @method \Spryker\Glue\ProductsRestApi\ProductsRestApiConfig getConfig()
 */
class ProductsRestApiDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_PRODUCT_STORAGE = 'CLIENT_PRODUCT_STORAGE';

    /**
     * @var string
     */
    public const CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    /**
     * @var string
     */
    public const CLIENT_STORE = 'CLIENT_STORE';

    /**
     * @var string
     */
    public const PLUGINS_CONCRETE_PRODUCTS_RESOURCE_EXPANDER = 'PLUGINS_CONCRETE_PRODUCTS_RESOURCE_EXPANDER';

    /**
     * @var string
     */
    public const PLUGINS_ABSTRACT_PRODUCTS_RESOURCE_EXPANDER = 'PLUGINS_ABSTRACT_PRODUCTS_RESOURCE_EXPANDER';

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);

        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addProductStorageClient($container);
        $container = $this->addStoreClient($container);
        $container = $this->addConcreteProductsResourceExpanderPlugins($container);
        $container = $this->addAbstractProductsResourceExpanderPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addProductStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_PRODUCT_STORAGE, function (Container $container) {
            return new ProductsRestApiToProductStorageClientBridge($container->getLocator()->productStorage()->client());
        });

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addGlossaryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY_STORAGE, function (Container $container) {
            return new ProductsRestApiToGlossaryStorageClientBridge($container->getLocator()->glossaryStorage()->client());
        });

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container) {
            return new ProductsRestApiToStoreClientBridge($container->getLocator()->store()->client());
        });

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addConcreteProductsResourceExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CONCRETE_PRODUCTS_RESOURCE_EXPANDER, function () {
            return $this->getConcreteProductsResourceExpanderPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addAbstractProductsResourceExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ABSTRACT_PRODUCTS_RESOURCE_EXPANDER, function () {
            return $this->getAbstractProductsResourceExpanderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\ConcreteProductsResourceExpanderPluginInterface>
     */
    protected function getConcreteProductsResourceExpanderPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Glue\ProductsRestApiExtension\Dependency\Plugin\AbstractProductsResourceExpanderPluginInterface>
     */
    protected function getAbstractProductsResourceExpanderPlugins(): array
    {
        return [];
    }
}
