<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
trait CustomerAddressValidationTrait
{
    private function validateAddress(string $id, SalesChannelContext $context, CustomerEntity $customer): void
    {
        $criteria = new Criteria([$id]);
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));

        if (\count($this->addressRepository->searchIds($criteria, $context->getContext())->getIds())) {
            return;
        }

        throw CustomerException::addressNotFound($id);
    }
}
