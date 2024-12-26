<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerVatIdentification;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Event\DataMappingEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\SalesChannel\SuccessResponse;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api'], '_contextTokenRequired' => true])]
#[Package('checkout')]
class ChangeCustomerProfileRoute extends AbstractChangeCustomerProfileRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataValidator $validator,
        private readonly DataValidationFactoryInterface $customerProfileValidationFactory,
        private readonly StoreApiCustomFieldMapper $storeApiCustomFieldMapper,
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    public function getDecorated(): AbstractChangeCustomerProfileRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/account/change-profile', name: 'store-api.account.change-profile', defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true], methods: ['POST'])]
    public function change(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        $validation = $this->customerProfileValidationFactory->update($context);

        if ($data->has('accountType') && empty($data->get('accountType'))) {
            $data->remove('accountType');
        }

        if ($data->get('accountType') === CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
            $validation->add('company', new NotBlank());
            $billingAddress = $customer->getDefaultBillingAddress();
            if ($billingAddress) {
                $this->addVatIdsValidation($validation, $billingAddress);
            }
        } else {
            $data->set('company', '');
            $data->set('vatIds', null);
        }

        /** @var ?RequestDataBag $vatIds */
        $vatIds = $data->get('vatIds');
        if ($vatIds) {
            $vatIds = \array_filter($vatIds->all());
            $data->set('vatIds', empty($vatIds) ? null : $vatIds);
        }

        if (!$data->get('salutationId')) {
            $data->set('salutationId', $this->getDefaultSalutationId($context));
        }

        $this->dispatchValidationEvent($validation, $data, $context->getContext());

        $this->validator->validate($data->all(), $validation);

        $customerData = $data->only('name', 'salutationId', 'title', 'company', 'accountType');

        if ($vatIds) {
            $vatIds = $data->get('vatIds');

            if ($vatIds instanceof DataBag) {
                $vatIds = $vatIds->all();
            }

            $customerData['vatIds'] = $vatIds;
        }

        if ($birthday = $this->getBirthday($data)) {
            $customerData['birthday'] = $birthday;
        }

        if ($data->get('customFields') instanceof RequestDataBag) {
            $customerData['customFields'] = $this->storeApiCustomFieldMapper->map(
                CustomerDefinition::ENTITY_NAME,
                $data->get('customFields')
            );
        }

        $mappingEvent = new DataMappingEvent($data, $customerData, $context->getContext());
        $this->eventDispatcher->dispatch($mappingEvent, CustomerEvents::MAPPING_CUSTOMER_PROFILE_SAVE);

        $customerData = $mappingEvent->getOutput();

        $customerData['id'] = $customer->getId();

        $this->customerRepository->update([$customerData], $context->getContext());

        return new SuccessResponse();
    }

    private function dispatchValidationEvent(DataValidationDefinition $definition, DataBag $data, Context $context): void
    {
        $validationEvent = new BuildValidationEvent($definition, $data, $context);
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());
    }

    private function addVatIdsValidation(DataValidationDefinition $validation, CustomerAddressEntity $address): void
    {
        /** @var Constraint[] $constraints */
        $constraints = [
            new Type('array'),
            new CustomerVatIdentification(
                ['countryId' => $address->getCountryId()]
            ),
        ];
        if ($address->getCountry() && $address->getCountry()->getVatIdRequired()) {
            $constraints[] = new NotBlank();
        }

        $validation->add('vatIds', ...$constraints);
    }

    private function getBirthday(DataBag $data): ?\DateTimeInterface
    {
        $birthdayDay = $data->get('birthdayDay');
        $birthdayMonth = $data->get('birthdayMonth');
        $birthdayYear = $data->get('birthdayYear');

        if (!$birthdayDay || !$birthdayMonth || !$birthdayYear) {
            return null;
        }
        \assert(\is_numeric($birthdayDay));
        \assert(\is_numeric($birthdayMonth));
        \assert(\is_numeric($birthdayYear));

        return new \DateTime(\sprintf(
            '%s-%s-%s',
            $birthdayYear,
            $birthdayMonth,
            $birthdayDay
        ));
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
