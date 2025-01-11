<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\AbstractTaxDetector;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Currency\Aggregate\CurrencyCountryRounding\CurrencyCountryRoundingCollection;
use Cicada\Core\System\SalesChannel\BaseContext;
use Cicada\Core\System\SalesChannel\Event\SalesChannelContextPermissionsChangedEvent;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleCollection;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\System\Tax\TaxRuleType\TaxRuleTypeFilterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('discovery')]
class SalesChannelContextFactory extends AbstractSalesChannelContextFactory
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     * @param EntityRepository<CustomerGroupCollection> $customerGroupRepository
     * @param EntityRepository<CustomerAddressCollection> $addressRepository
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     * @param iterable<TaxRuleTypeFilterInterface> $taxRuleTypeFilter
     * @param EntityRepository<CurrencyCountryRoundingCollection> $currencyCountryRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $customerGroupRepository,
        private readonly EntityRepository $addressRepository,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly AbstractTaxDetector $taxDetector,
        private readonly iterable $taxRuleTypeFilter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $currencyCountryRepository,
        private readonly AbstractBaseContextFactory $baseContextFactory
    ) {
    }

    public function getDecorated(): AbstractSalesChannelContextFactory
    {
        throw new DecorationPatternException(self::class);
    }

    public function create(string $token, string $salesChannelId, array $options = []): SalesChannelContext
    {
        // we split the context generation to allow caching of the base context
        $base = $this->baseContextFactory->create($salesChannelId, $options);

        // customer
        $customer = null;
        if (\array_key_exists(SalesChannelContextService::CUSTOMER_ID, $options) && $options[SalesChannelContextService::CUSTOMER_ID] !== null) {
            // load logged in customer and set active addresses
            $customer = $this->loadCustomer($options, $base->getContext());
        }

        $shippingLocation = $base->getShippingLocation();
        if ($customer) {
            /** @var CustomerAddressEntity $activeShippingAddress */
            $activeShippingAddress = $customer->getActiveShippingAddress();
            if ($activeShippingAddress) {
                $shippingLocation = ShippingLocation::createFromAddress($activeShippingAddress);
            }
        }

        $customerGroup = $base->getCurrentCustomerGroup();

        if ($customer) {
            $criteria = new Criteria([$customer->getGroupId()]);
            $criteria->setTitle('context-factory::customer-group');
            $customerGroup = $this->customerGroupRepository->search($criteria, $base->getContext())->getEntities()->first() ?? $customerGroup;
        }

        // loads tax rules based on active customer and delivery address
        $taxRules = $this->getTaxRules($base, $customer, $shippingLocation);

        // detect active payment method, first check if checkout defined other payment method, otherwise validate if customer logged in, at least use shop default
        $payment = $this->getPaymentMethod($options, $base, $customer);

        [$itemRounding, $totalRounding] = $this->getCashRounding($base, $shippingLocation);

        $context = new Context(
            $base->getContext()->getSource(),
            [],
            $base->getCurrencyId(),
            $base->getContext()->getLanguageIdChain(),
            $base->getContext()->getVersionId(),
            $base->getCurrency()->getFactor(),
            true,
            CartPrice::TAX_STATE_GROSS,
            $itemRounding
        );

        $salesChannelContext = new SalesChannelContext(
            $context,
            $token,
            $options[SalesChannelContextService::DOMAIN_ID] ?? null,
            $base->getSalesChannel(),
            $base->getCurrency(),
            $customerGroup,
            $taxRules,
            $payment,
            $base->getShippingMethod(),
            $shippingLocation,
            $customer,
            $itemRounding,
            $totalRounding,
            [],
            $base->getLanguageInfo(),
        );

        if (\array_key_exists(SalesChannelContextService::PERMISSIONS, $options)) {
            $salesChannelContext->setPermissions($options[SalesChannelContextService::PERMISSIONS]);

            $event = new SalesChannelContextPermissionsChangedEvent($salesChannelContext, $options[SalesChannelContextService::PERMISSIONS]);
            $this->eventDispatcher->dispatch($event);

            $salesChannelContext->lockPermissions();
        }

        if (\array_key_exists(SalesChannelContextService::IMITATING_USER_ID, $options)) {
            $salesChannelContext->setImitatingUserId($options[SalesChannelContextService::IMITATING_USER_ID]);
        }

        $salesChannelContext->setTaxState($this->taxDetector->getTaxState($salesChannelContext));

        return $salesChannelContext;
    }

    private function getTaxRules(BaseContext $context, ?CustomerEntity $customer, ShippingLocation $shippingLocation): TaxCollection
    {
        $taxes = $context->getTaxRules()->getElements();

        foreach ($taxes as $tax) {
            $taxRules = $tax->getRules();
            if ($taxRules === null) {
                continue;
            }

            $taxRules = $taxRules->filter(function (TaxRuleEntity $taxRule) use ($customer, $shippingLocation) {
                foreach ($this->taxRuleTypeFilter as $ruleTypeFilter) {
                    if ($ruleTypeFilter->match($taxRule, $customer, $shippingLocation)) {
                        return true;
                    }
                }

                return false;
            });

            $matchingRules = new TaxRuleCollection();
            $taxRule = $taxRules->highestTypePosition();

            if (!$taxRule) {
                $tax->setRules($matchingRules);

                continue;
            }

            $taxRules = $taxRules->filterByTypePosition($taxRule->getType()->getPosition());
            $taxRule = $taxRules->latestActivationDate();

            if ($taxRule) {
                $matchingRules->add($taxRule);
            }
            $tax->setRules($matchingRules);
        }

        return new TaxCollection($taxes);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array<string, mixed> $options
     */
    private function getPaymentMethod(array $options, BaseContext $context, ?CustomerEntity $customer): PaymentMethodEntity
    {
        if ($customer === null || isset($options[SalesChannelContextService::PAYMENT_METHOD_ID])) {
            return $context->getPaymentMethod();
        }

        $id = $customer->getLastPaymentMethodId();

        if ($id === null || $id === $context->getPaymentMethod()->getId()) {
            return $context->getPaymentMethod();
        }

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('media');
        $criteria->addAssociation('appPaymentMethod');
        $criteria->setTitle('context-factory::payment-method');
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $context->getSalesChannelId()));

        $paymentMethod = $this->paymentMethodRepository->search($criteria, $context->getContext())->getEntities()->get($id);

        if (!$paymentMethod) {
            return $context->getPaymentMethod();
        }

        return $paymentMethod;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function loadCustomer(array $options, Context $context): ?CustomerEntity
    {
        $customerId = $options[SalesChannelContextService::CUSTOMER_ID];

        $criteria = new Criteria([$customerId]);
        $criteria->setTitle('context-factory::customer');
        $criteria->addAssociation('salutation');

        $source = $context->getSource();
        \assert($source instanceof SalesChannelApiSource);

        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('customer.boundSalesChannelId', null),
            new EqualsFilter('customer.boundSalesChannelId', $source->getSalesChannelId()),
        ]));

        $customer = $this->customerRepository->search($criteria, $context)->getEntities()->get($customerId);

        if (!$customer) {
            return null;
        }

        $activeBillingAddressId = $options[SalesChannelContextService::BILLING_ADDRESS_ID] ?? $customer->getDefaultBillingAddressId();
        $activeShippingAddressId = $options[SalesChannelContextService::SHIPPING_ADDRESS_ID] ?? $customer->getDefaultShippingAddressId();

        $addressIds = [];

        if ($activeBillingAddressId) {
            $addressIds[] = $activeBillingAddressId;
        }
        if ($activeShippingAddressId) {
            $addressIds[] = $activeShippingAddressId;
        }

        if ($defaultBillingAddressId = $customer->getDefaultBillingAddressId()) {
            $addressIds[] = $defaultBillingAddressId;
        }
        if ($defaultShippingAddressId = $customer->getDefaultShippingAddressId()) {
            $addressIds[] = $defaultShippingAddressId;
        }

        if (!empty($addressIds)) {
            $criteria = new Criteria(\array_unique($addressIds));
            $criteria->setTitle('context-factory::addresses');
            $criteria->addAssociation('salutation');
            $criteria->addAssociation('country');
            $criteria->addAssociation('countryState');

            $addresses = $this->addressRepository->search($criteria, $context)->getEntities();

            $activeBillingAddress = null;
            if ($activeBillingAddressId !== null) {
                $activeBillingAddress = $addresses->get($activeBillingAddressId);
            } else {
                $defaultBillingAddressId = $customer->getDefaultBillingAddressId();
                if ($defaultBillingAddressId !== null) {
                    $activeBillingAddress = $addresses->get($defaultBillingAddressId);
                }
            }

            if ($activeBillingAddress !== null) {
                $customer->setDefaultBillingAddress($activeBillingAddress);
            }

            $activeShippingAddress = null;
            if ($activeShippingAddressId !== null) {
                $activeShippingAddress = $addresses->get($activeShippingAddressId);
            } else {
                $defaultShippingAddressId = $customer->getDefaultShippingAddressId();
                if ($defaultShippingAddressId !== null) {
                    $activeShippingAddress = $addresses->get($defaultShippingAddressId);
                }
            }

            if ($activeShippingAddress !== null) {
                $customer->setActiveShippingAddress($activeShippingAddress);
            }

            $defaultBillingAddressId = $customer->getDefaultBillingAddressId();
            if ($defaultBillingAddressId !== null) {
                $defaultBillingAddress = $addresses->get($defaultBillingAddressId);
                if ($defaultBillingAddress !== null) {
                    $customer->setDefaultBillingAddress($defaultBillingAddress);
                }
            }

            $defaultShippingAddressId = $customer->getDefaultShippingAddressId();
            if ($defaultShippingAddressId !== null) {
                $defaultShippingAddress = $addresses->get($defaultShippingAddressId);
                if ($defaultShippingAddress !== null) {
                    $customer->setDefaultShippingAddress($defaultShippingAddress);
                }
            }
        }

        return $customer;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array{CashRoundingConfig, CashRoundingConfig}
     */
    private function getCashRounding(BaseContext $context, ShippingLocation $shippingLocation): array
    {
        if ($context->getShippingLocation()->getCountry()->getId() === $shippingLocation->getCountry()->getId()) {
            return [$context->getItemRounding(), $context->getTotalRounding()];
        }

        $criteria = new Criteria();
        $criteria->setTitle('context-factory::cash-rounding');
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('currencyId', $context->getCurrencyId()));
        $criteria->addFilter(new EqualsFilter('countryId', $shippingLocation->getCountry()->getId()));

        $countryConfig = $this->currencyCountryRepository
            ->search($criteria, $context->getContext())
            ->getEntities()
            ->first();

        if ($countryConfig) {
            return [$countryConfig->getItemRounding(), $countryConfig->getTotalRounding()];
        }

        return [$context->getCurrency()->getItemRounding(), $context->getCurrency()->getTotalRounding()];
    }
}
