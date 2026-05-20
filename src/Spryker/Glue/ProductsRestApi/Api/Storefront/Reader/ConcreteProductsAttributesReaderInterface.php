<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Reader;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;

interface ConcreteProductsAttributesReaderInterface
{
    public function findConcreteProductAttributes(string $sku, string $localeName): ?ConcreteProductsRestAttributesTransfer;

    /**
     * @param array<int> $concreteProductIds
     *
     * @return array<int, \Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer>
     */
    public function findBulkConcreteProductAttributesByIds(array $concreteProductIds, string $localeName): array;
}
