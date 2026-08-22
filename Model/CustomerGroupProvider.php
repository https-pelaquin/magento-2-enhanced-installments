<?php
/**
 *  Copyright © Bruno Pelaquin. All rights reserved.
 *
 *  https://github.com/https-pelaquin
 *  https://www.linkedin.com/in/bruno-pelaquin/
 */

declare(strict_types=1);

namespace Pelaquin\EnhancedInstallments\Model;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Http\Context;

class CustomerGroupProvider
{
    public function __construct(
        private readonly Context $httpContext
    ) {
    }

    public function getCustomerGroupId(): int
    {
        $customerGroupId = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);

        return is_numeric($customerGroupId)
            ? max(0, (int) $customerGroupId)
            : Group::NOT_LOGGED_IN_ID;
    }
}
