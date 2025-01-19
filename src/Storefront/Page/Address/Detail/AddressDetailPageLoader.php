<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Address\Detail;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
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
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\SalesChannel\AbstractCountryRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Salutation\AbstractSalutationsSorter;
use Cicada\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class AddressDetailPageLoader
{
    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - translator will be mandatory from 6.7
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly AbstractCountryRoute $countryRoute,
        private readonly AbstractSalutationRoute $salutationRoute,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractListAddressRoute $listAddressRoute,
        private readonly AbstractSalutationsSorter $salutationsSorter,
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
    public function load(Request $request, SalesChannelContext $salesChannelContext, CustomerEntity $customer): AddressDetailPage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = AddressDetailPage::createFrom($page);
        $this->setMetaInformation($page, $request);

        $page->setSalutations($this->getSalutations($salesChannelContext));

        $page->setCountries($this->getCountries($salesChannelContext));

        $page->setAddress($this->getAddress($request, $salesChannelContext, $customer));

        $this->eventDispatcher->dispatch(
            new AddressDetailPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(AddressDetailPage $page, Request $request): void
    {
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        if ($this->translator !== null && $page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        if ($this->translator !== null && $request->attributes->get('_route') === 'frontend.account.address.create.page') {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('account.addressCreateMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        } elseif ($this->translator !== null && $request->attributes->get('_route') === 'frontend.account.address.edit.page') {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('account.addressEditMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        }
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getSalutations(SalesChannelContext $salesChannelContext): SalutationCollection
    {
        $salutations = $this->salutationRoute->load(new Request(), $salesChannelContext, new Criteria())->getSalutations();

        return $this->salutationsSorter->sort($salutations);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getCountries(SalesChannelContext $salesChannelContext): CountryCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('country.active', true))
            ->addAssociation('states');
        $criteria->getAssociation('states')->addFilter(new EqualsFilter('parentId', null));

        $countries = $this->countryRoute->load(new Request(), $criteria, $salesChannelContext)->getCountries();

        $countries->sortCountryAndStates();

        return $countries;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidUuidException
     */
    private function getAddress(Request $request, SalesChannelContext $context, CustomerEntity $customer): ?CustomerAddressEntity
    {
        if (!$request->get('addressId')) {
            return null;
        }
        $addressId = $request->get('addressId');

        if (!Uuid::isValid($addressId)) {
            throw new InvalidUuidException($addressId);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $addressId));
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));

        $address = $this->listAddressRoute->load($criteria, $context, $customer)->getAddressCollection()->get($addressId);

        if (!$address) {
            throw CustomerException::addressNotFound($addressId);
        }

        return $address;
    }
}
