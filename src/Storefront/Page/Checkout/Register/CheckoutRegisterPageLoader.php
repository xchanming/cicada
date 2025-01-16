<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Register;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Cicada\Core\Checkout\Customer\SalesChannel\AbstractListAddressRoute;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Uuid\UuidException;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\SalesChannel\AbstractCountryRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationEntity;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class CheckoutRegisterPageLoader
{
    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - translator will be mandatory from 6.7
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly AbstractListAddressRoute $listAddressRoute,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CartService $cartService,
        private readonly AbstractSalutationRoute $salutationRoute,
        private readonly AbstractCountryRoute $countryRoute,
        private readonly ?AbstractTranslator $translator = null
    ) {
    }

    /**
     * @throws AddressNotFoundException
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidUuidException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): CheckoutRegisterPage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = CheckoutRegisterPage::createFrom($page);
        $this->setMetaInformation($page);

        $page->setCountries($this->getCountries($salesChannelContext));
        $page->setCart($this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext));
        $page->setSalutations($this->getSalutations($salesChannelContext));

        $addressId = $request->attributes->get('addressId');
        if ($addressId) {
            $address = $this->getById((string) $addressId, $salesChannelContext);
            $page->setAddress($address);
        }

        $this->eventDispatcher->dispatch(
            new CheckoutRegisterPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(CheckoutRegisterPage $page): void
    {
        /**
         * @deprecated tag:v6.7.0 - Remove condition in 6.7.
         */
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        /**
         * @deprecated tag:v6.7.0 - Remove condition with body in 6.7.
         */
        if ($this->translator !== null && $page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        if ($this->translator !== null) {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('checkout.registerMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        }
    }

    private function getById(string $addressId, SalesChannelContext $context): CustomerAddressEntity
    {
        if (!Uuid::isValid($addressId)) {
            throw UuidException::invalidUuid($addressId);
        }

        if ($context->getCustomer() === null) {
            throw CartException::customerNotLoggedIn();
        }
        $customer = $context->getCustomer();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $addressId));
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));

        $address = $this->listAddressRoute->load($criteria, $context, $customer)->getAddressCollection()->get($addressId);

        if (!$address) {
            throw CustomerException::addressNotFound($addressId);
        }

        return $address;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getSalutations(SalesChannelContext $salesChannelContext): SalutationCollection
    {
        $salutations = $this->salutationRoute->load(new Request(), $salesChannelContext, new Criteria())->getSalutations();

        $salutations->sort(fn (SalutationEntity $a, SalutationEntity $b) => $b->getSalutationKey() <=> $a->getSalutationKey());

        return $salutations;
    }

    private function getCountries(SalesChannelContext $salesChannelContext): CountryCollection
    {
        $countries = $this->countryRoute->load(new Request(), new Criteria(), $salesChannelContext)->getCountries();

        $countries->sortCountryAndStates();

        return $countries;
    }
}
