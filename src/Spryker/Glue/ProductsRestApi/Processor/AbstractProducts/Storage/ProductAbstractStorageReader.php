<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\Storage;

use Generated\Shared\Transfer\ResourceIdentifierTransfer;
use Generated\Shared\Transfer\UrlStorageTransfer;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;

class ProductAbstractStorageReader implements ProductAbstractStorageReaderInterface
{
    protected const KEY_SKU = 'sku';

    /**
     * @var \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface
     */
    protected $productStorageClient;

    /**
     * @param \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface $productStorageClient
     */
    public function __construct(ProductsRestApiToProductStorageClientInterface $productStorageClient)
    {
        $this->productStorageClient = $productStorageClient;
    }

    /**
     * @param \Generated\Shared\Transfer\UrlStorageTransfer $urlStorageTransfer
     *
     * @return \Generated\Shared\Transfer\ResourceIdentifierTransfer|null
     */
    public function provideResourceIdentifierByUrlStorageTransfer(UrlStorageTransfer $urlStorageTransfer): ?ResourceIdentifierTransfer
    {
        $localeName = $this->findLocaleName($urlStorageTransfer);

        if (!$localeName) {
            return null;
        }

        $data = $this->productStorageClient->findProductAbstractStorageData(
            $urlStorageTransfer->getFkResourceProductAbstract(),
            $localeName
        );

        if (!$data || !$data[static::KEY_SKU]) {
            return null;
        }

        return (new ResourceIdentifierTransfer())
            ->setType(ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS)
            ->setId($data[static::KEY_SKU]);
    }

    /**
     * @param \Generated\Shared\Transfer\UrlStorageTransfer $urlStorageTransfer
     *
     * @return string|null
     */
    protected function findLocaleName(UrlStorageTransfer $urlStorageTransfer): ?string
    {
        if ($urlStorageTransfer->getLocaleName()) {
            return $urlStorageTransfer->getLocaleName();
        }

        foreach ($urlStorageTransfer->getLocaleUrls() as $localeUrlStorageTransfer) {
            if ($localeUrlStorageTransfer->getFkLocale() !== $urlStorageTransfer->getFkLocale()) {
                continue;
            }

            return $localeUrlStorageTransfer->getLocaleName();
        }

        return null;
    }
}
