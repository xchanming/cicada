<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Address;

use Cicada\Core\Checkout\Cart\Address\Error\BillingAddressCountryRegionMissingError;
use Cicada\Core\Checkout\Cart\Address\Error\BillingAddressSalutationMissingError;
use Cicada\Core\Checkout\Cart\Address\Error\ShippingAddressBlockedError;
use Cicada\Core\Checkout\Cart\Address\Error\ShippingAddressCountryRegionMissingError;
use Cicada\Core\Checkout\Cart\Address\Error\ShippingAddressSalutationMissingError;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartValidatorInterface;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
class AddressValidator implements CartValidatorInterface, ResetInterface
{
    /**
     * @var array<string, bool>
     */
    private array $available = [];

    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $repository)
    {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        $country = $context->getShippingLocation()->getCountry();
        $customer = $context->getCustomer();
        $validateShipping = $cart->getLineItems()->count() === 0
            || $cart->getLineItems()->hasLineItemWithState(State::IS_PHYSICAL);

        if (!$country->getActive() && $validateShipping) {
            $errors->add(new ShippingAddressBlockedError((string) $country->getTranslation('name')));

            return;
        }

        if (!$country->getShippingAvailable() && $validateShipping) {
            $errors->add(new ShippingAddressBlockedError((string) $country->getTranslation('name')));

            return;
        }

        if (!$this->isSalesChannelCountry($country->getId(), $context) && $validateShipping) {
            $errors->add(new ShippingAddressBlockedError((string) $country->getTranslation('name')));

            return;
        }

        if ($customer === null) {
            return;
        }

        if ($customer->getActiveBillingAddress() === null || $customer->getActiveShippingAddress() === null) {
            // No need to add salutation-specific errors in this case
            return;
        }

        if (!$customer->getActiveBillingAddress()->getSalutationId()) {
            $errors->add(new BillingAddressSalutationMissingError($customer->getActiveBillingAddress()));

            return;
        }

        if (!$customer->getActiveShippingAddress()->getSalutationId() && $validateShipping) {
            $errors->add(new ShippingAddressSalutationMissingError($customer->getActiveShippingAddress()));
        }

        if ($customer->getActiveBillingAddress()->getCountry()?->getForceStateInRegistration()) {
            if (!$customer->getActiveBillingAddress()->getCountryState()) {
                $errors->add(new BillingAddressCountryRegionMissingError($customer->getActiveBillingAddress()));
            }
        }

        if ($customer->getActiveShippingAddress()->getCountry()?->getForceStateInRegistration()) {
            if (!$customer->getActiveShippingAddress()->getCountryState()) {
                $errors->add(new ShippingAddressCountryRegionMissingError($customer->getActiveShippingAddress()));
            }
        }
    }

    public function reset(): void
    {
        $this->available = [];
    }

    private function isSalesChannelCountry(string $countryId, SalesChannelContext $context): bool
    {
        if (isset($this->available[$countryId])) {
            return $this->available[$countryId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $context->getSalesChannelId()));
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));

        return $this->available[$countryId] = $this->repository->searchIds($criteria, $context->getContext())->getTotal() !== 0;
    }
}
