<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerZipCode;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Event\DataMappingEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class UpsertAddressRoute extends AbstractUpsertAddressRoute
{
    use CustomerAddressValidationTrait;

    /**
     * @internal
     *
     * @param EntityRepository<CustomerAddressCollection> $addressRepository
     */
    public function __construct(
        private readonly EntityRepository $addressRepository,
        private readonly DataValidator $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataValidationFactoryInterface $addressValidationFactory,
        private readonly SystemConfigService $systemConfigService,
        private readonly StoreApiCustomFieldMapper $storeApiCustomFieldMapper,
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    public function getDecorated(): AbstractUpsertAddressRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(
        path: '/store-api/account/address',
        name: 'store-api.account.address.create',
        defaults: ['addressId' => null, '_loginRequired' => true, '_loginRequiredAllowGuest' => true],
        methods: ['POST']
    )]
    #[Route(
        path: '/store-api/account/address/{addressId}',
        name: 'store-api.account.address.update',
        defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true],
        methods: ['PATCH']
    )]
    public function upsert(?string $addressId, RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): UpsertAddressRouteResponse
    {
        if (!$addressId) {
            $isCreate = true;
            $addressId = Uuid::randomHex();
        } else {
            $this->validateAddress($addressId, $context, $customer);
            $isCreate = false;
        }

        if (!$data->get('salutationId')) {
            $data->set('salutationId', $this->getDefaultSalutationId($context));
        }

        $accountType = $data->get('accountType', CustomerEntity::ACCOUNT_TYPE_PRIVATE);
        $definition = $this->getValidationDefinition($data, $accountType, $isCreate, $context);
        $this->validator->validate(array_merge(['id' => $addressId], $data->all()), $definition);

        $addressData = [
            'salutationId' => $data->get('salutationId'),
            'name' => $data->get('name'),
            'street' => $data->get('street'),
            'cityId' => $data->get('cityId'),
            'zipcode' => $data->get('zipcode'),
            'countryId' => $data->get('countryId'),
            'countryStateId' => $data->get('countryStateId') ?: null,
            'company' => $data->get('company'),
            'department' => $data->get('department'),
            'title' => $data->get('title'),
            'phoneNumber' => $data->get('phoneNumber'),
            'additionalAddressLine1' => $data->get('additionalAddressLine1'),
            'additionalAddressLine2' => $data->get('additionalAddressLine2'),
        ];

        if ($data->get('customFields') instanceof RequestDataBag) {
            $addressData['customFields'] = $this->storeApiCustomFieldMapper->map(
                CustomerAddressDefinition::ENTITY_NAME,
                $data->get('customFields')
            );
        }

        $mappingEvent = new DataMappingEvent($data, $addressData, $context->getContext());
        $this->eventDispatcher->dispatch($mappingEvent, CustomerEvents::MAPPING_ADDRESS_CREATE);

        $addressData = $mappingEvent->getOutput();
        $addressData['id'] = $addressId;
        $addressData['customerId'] = $customer->getId();

        $this->addressRepository->upsert([$addressData], $context->getContext());

        $criteria = new Criteria([$addressId]);

        /** @var CustomerAddressEntity $address */
        $address = $this->addressRepository->search($criteria, $context->getContext())->first();

        return new UpsertAddressRouteResponse($address);
    }

    private function getValidationDefinition(DataBag $data, string $accountType, bool $isCreate, SalesChannelContext $context): DataValidationDefinition
    {
        if ($isCreate) {
            $validation = $this->addressValidationFactory->create($context);
        } else {
            $validation = $this->addressValidationFactory->update($context);
        }

        if ($accountType === CustomerEntity::ACCOUNT_TYPE_BUSINESS && $this->systemConfigService->get('core.loginRegistration.showAccountTypeSelection')) {
            $validation->add('company', new NotBlank());
        }

        $validation->set('zipcode', new CustomerZipCode(['countryId' => $data->get('countryId')]));

        $validationEvent = new BuildValidationEvent($validation, $data, $context->getContext());
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        return $validation;
    }

    private function getDefaultSalutationId(SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new EqualsFilter('salutationKey', SalutationDefinition::NOT_SPECIFIED)
        );

        /** @var array<string> $ids */
        $ids = $this->salutationRepository->searchIds($criteria, $context->getContext())->getIds();

        return $ids[0] ?? null;
    }
}
