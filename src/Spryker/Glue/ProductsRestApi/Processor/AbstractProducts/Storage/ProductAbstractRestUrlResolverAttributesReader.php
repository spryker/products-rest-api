<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ProductsRestApi\Processor\AbstractProducts\Storage;

use Generated\Shared\Transfer\RestUrlResolverAttributesTransfer;
use Generated\Shared\Transfer\UrlStorageTransfer;
use Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface;
use Spryker\Glue\ProductsRestApi\ProductsRestApiConfig;

class ProductAbstractRestUrlResolverAttributesReader implements ProductAbstractRestUrlResolverAttributesReaderInterface
{
    /**
     * @var string
     */
    protected const KEY_SKU = 'sku';

    /**
     * @var \Spryker\Glue\ProductsRestApi\Dependency\Client\ProductsRestApiToProductStorageClientInterface
     */
    protected $productStorageClient;

    public function __construct(ProductsRestApiToProductStorageClientInterface $productStorageClient)
    {
        $this->productStorageClient = $productStorageClient;
    }

    public function provideRestUrlResolverAttributesTransferByUrlStorageTransfer(UrlStorageTransfer $urlStorageTransfer): ?RestUrlResolverAttributesTransfer
    {
        $localeName = $this->findLocaleName($urlStorageTransfer);

        if (!$localeName) {
            return null;
        }

        $data = $this->productStorageClient->findProductAbstractStorageData(
            $urlStorageTransfer->getFkResourceProductAbstract(),
            $localeName,
        );

        if (!$data) {
            return null;
        }

        return (new RestUrlResolverAttributesTransfer())
            ->setEntityType(ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS)
            ->setEntityId($data[static::KEY_SKU]);
    }

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
