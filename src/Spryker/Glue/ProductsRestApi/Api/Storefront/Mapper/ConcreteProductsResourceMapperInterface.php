<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;

interface ConcreteProductsResourceMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function mapConcreteProductsRestAttributesTransferToResourceData(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer,
    ): array;
}
