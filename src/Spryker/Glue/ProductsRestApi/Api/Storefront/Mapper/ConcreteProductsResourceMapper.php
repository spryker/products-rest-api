<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\ProductsRestApi\Api\Storefront\Mapper;

use Generated\Shared\Transfer\ConcreteProductsRestAttributesTransfer;

class ConcreteProductsResourceMapper implements ConcreteProductsResourceMapperInterface
{
    /**
     * @return array<string, mixed>
     */
    public function mapConcreteProductsRestAttributesTransferToResourceData(
        ConcreteProductsRestAttributesTransfer $concreteProductsRestAttributesTransfer,
    ): array {
        $data = $concreteProductsRestAttributesTransfer->toArray(false, true);
        $data['reviewCount'] = $concreteProductsRestAttributesTransfer->getReviewCount() ?? 0;
        $data['productConfigurationInstance'] = $concreteProductsRestAttributesTransfer->getProductConfigurationInstance()?->toArray() ?? [];

        return $data;
    }
}
