<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper;

use Generated\Shared\Transfer\AbstractProductsRestAttributesTransfer;

interface AbstractProductsResourceMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function mapAbstractProductsAttributesTransferToResourceData(
        AbstractProductsRestAttributesTransfer $abstractProductsRestAttributesTransfer,
    ): array;
}
