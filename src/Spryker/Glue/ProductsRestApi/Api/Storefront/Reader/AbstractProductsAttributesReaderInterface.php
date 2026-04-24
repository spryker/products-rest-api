<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Reader;

use Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer;

interface AbstractProductsAttributesReaderInterface
{
    public function findAbstractProductAttributes(string $sku, string $localeName): ?AbstractProductsRestAttributesTransfer;
}
