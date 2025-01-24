<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerEvents;
use Cicada\Core\Checkout\Customer\Event\CustomerConfirmRegisterUrlEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Cicada\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent;
use Cicada\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Cicada\Core\Checkout\Customer\Service\EmailIdnConverter;
use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerEmailUnique;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Event\DataMappingEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class RegisterRoute extends AbstractRegisterRoute
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     * @param EntityRepository<SalutationCollection> $salutationRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        private readonly DataValidator $validator,
        private readonly DataValidationFactoryInterface $accountValidationFactory,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $customerRepository,
        private readonly SalesChannelContextPersister $contextPersister,
        protected Connection $connection,
        private readonly SalesChannelContextServiceInterface $contextService,
        private readonly StoreApiCustomFieldMapper $customFieldMapper,
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    public function getDecorated(): AbstractRegisterRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/account/register', name: 'store-api.account.register', methods: ['POST'])]
    public function register(RequestDataBag $data, SalesChannelContext $context, bool $validateStorefrontUrl = true, ?DataValidationDefinition $additionalValidationDefinitions = null): CustomerResponse
    {
        EmailIdnConverter::encodeDataBag($data);

        $isGuest = $data->getBoolean('guest');

        if ($data->has('accountType') && empty($data->get('accountType'))) {
            $data->remove('accountType');
        }

        if (!$data->get('salutationId')) {
            $data->set('salutationId', $this->getDefaultSalutationId($context));
        }

        if (!$data->get('title')) {
            $data->set('title', explode('@', $data->get('email'))[0]);
        }

        $this->validateRegistrationData($data, $isGuest, $context, $additionalValidationDefinitions, $validateStorefrontUrl);

        $customer = $this->mapCustomerData($data, $isGuest, $context);

        if ($data->get('accountType')) {
            $customer['accountType'] = $data->get('accountType');
        }

        $customer = $this->addDoubleOptInData($customer, $context);

        $customer['boundSalesChannelId'] = $this->getBoundSalesChannelId($customer['email'], $context);

        if ($data->get('customFields') instanceof RequestDataBag) {
            $customer['customFields'] = $this->customFieldMapper->map(CustomerDefinition::ENTITY_NAME, $data->get('customFields'));
        }

        // Convert all DataBags to array
        $customer = array_map(static function (mixed $value) {
            if ($value instanceof DataBag) {
                return $value->all();
            }

            return $value;
        }, $customer);

        $writeContext = clone $context->getContext();
        $writeContext->addState(EntityIndexerRegistry::USE_INDEXING_QUEUE);

        $this->customerRepository->create([$customer], $writeContext);

        $criteria = new Criteria([$customer['id']]);

        $criteria->addAssociation('salutation');

        /** @var CustomerEntity $customerEntity */
        $customerEntity = $this->customerRepository->search($criteria, $context->getContext())->first();

        if ($customerEntity->getDoubleOptInRegistration()) {
            $this->eventDispatcher->dispatch(
                $this->getDoubleOptInEvent(
                    $customerEntity,
                    $context,
                    $data->get('storefrontUrl'),
                    $data->get('redirectTo'),
                    $data->get('redirectParameters')
                )
            );

            // We don't want to leak the hash in store-api
            $customerEntity->setHash('');

            return new CustomerResponse($customerEntity);
        }

        $response = new CustomerResponse($customerEntity);

        $newToken = $this->contextPersister->replace($context->getToken(), $context);

        $this->contextPersister->save(
            $newToken,
            [
                'customerId' => $customerEntity->getId(),
                'billingAddressId' => null,
                'shippingAddressId' => null,
                'domainId' => $context->getDomainId(),
            ],
            $context->getSalesChannelId(),
            $customerEntity->getId()
        );

        $new = $this->contextService->get(
            new SalesChannelContextServiceParameters(
                $context->getSalesChannelId(),
                $newToken,
                $context->getLanguageId(),
                $context->getCurrencyId(),
                $context->getDomainId(),
                null,
                $customerEntity->getId()
            )
        );

        $new->addState(...$context->getStates());

        if (!$customerEntity->getGuest()) {
            $this->eventDispatcher->dispatch(new CustomerRegisterEvent($new, $customerEntity));
        } else {
            $this->eventDispatcher->dispatch(new GuestCustomerRegisterEvent($new, $customerEntity));
        }

        $event = new CustomerLoginEvent($new, $customerEntity, $newToken);
        $this->eventDispatcher->dispatch($event);

        $response->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $newToken);

        // We don't want to leak the hash in store-api
        $customerEntity->setHash('');

        return $response;
    }

    private function getDoubleOptInEvent(
        CustomerEntity $customer,
        SalesChannelContext $context,
        string $url,
        ?string $redirectTo,
        ?string $redirectParameters
    ): Event {
        $url .= $this->getConfirmUrl($context, $customer);

        if ($redirectTo) {
            $params = \is_string($redirectParameters) ? (\json_decode($redirectParameters, true) ?? []) : [];
            $url .= '&' . \http_build_query(array_merge(['redirectTo' => $redirectTo], $params));
        }

        if ($customer->getGuest()) {
            $event = new DoubleOptInGuestOrderEvent($customer, $context, $url);
        } else {
            $event = new CustomerDoubleOptInRegistrationEvent($customer, $context, $url);
        }

        return $event;
    }

    /**
     * @param array<string, mixed> $customer
     *
     * @return array<string, mixed>
     */
    private function addDoubleOptInData(array $customer, SalesChannelContext $context): array
    {
        $configKey = $customer['guest']
            ? 'core.loginRegistration.doubleOptInGuestOrder'
            : 'core.loginRegistration.doubleOptInRegistration';

        $doubleOptInRequired = $this->systemConfigService
            ->get($configKey, $context->getSalesChannelId());

        if (!$doubleOptInRequired) {
            return $customer;
        }

        $customer['doubleOptInRegistration'] = true;
        $customer['doubleOptInEmailSentDate'] = new \DateTimeImmutable();
        $customer['hash'] = Uuid::randomHex();

        return $customer;
    }

    private function validateRegistrationData(
        DataBag $data,
        bool $isGuest,
        SalesChannelContext $context,
        ?DataValidationDefinition $additionalValidations,
        bool $validateStorefrontUrl
    ): void {
        $definition = $this->getCustomerCreateValidationDefinition($isGuest, $data, $context);

        if ($additionalValidations) {
            foreach ($additionalValidations->getProperties() as $key => $validation) {
                $definition->add($key, ...$validation);
            }
        }

        if ($validateStorefrontUrl) {
            $definition
                ->add('storefrontUrl', new NotBlank(), new Choice(array_values($this->getDomainUrls($context))));
        }

        if ($this->systemConfigService->get('core.loginRegistration.requireDataProtectionCheckbox', $context->getSalesChannelId())) {
            $definition->add('acceptedDataProtection', new NotBlank());
        }

        $violations = $this->validator->getViolations($data->all(), $definition);

        if (!$violations->count()) {
            return;
        }

        throw new ConstraintViolationException($violations, $data->all());
    }

    /**
     * @return array<int, string>
     */
    private function getDomainUrls(SalesChannelContext $context): array
    {
        /** @var SalesChannelDomainCollection $salesChannelDomainCollection */
        $salesChannelDomainCollection = $context->getSalesChannel()->getDomains();

        return array_map(static fn (SalesChannelDomainEntity $domainEntity) => rtrim($domainEntity->getUrl(), '/'), $salesChannelDomainCollection->getElements());
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomerData(DataBag $data, bool $isGuest, SalesChannelContext $context): array
    {
        $customer = [
            'customerNumber' => $this->numberRangeValueGenerator->getValue(
                $this->customerRepository->getDefinition()->getEntityName(),
                $context->getContext(),
                $context->getSalesChannelId()
            ),
            'salesChannelId' => $context->getSalesChannelId(),
            'languageId' => $context->getLanguageId(),
            'groupId' => $context->getCustomerGroupId(),
            'requestedGroupId' => $data->get('requestedGroupId', null),
            'salutationId' => $data->get('salutationId'),
            'name' => $data->get('name'),
            'email' => $data->get('email'),
            'title' => $data->get('title'),
            'affiliateCode' => $data->get(OrderService::AFFILIATE_CODE_KEY),
            'campaignCode' => $data->get(OrderService::CAMPAIGN_CODE_KEY),
            'active' => true,
            'guest' => $isGuest,
            'firstLogin' => new \DateTimeImmutable(),
            'addresses' => [],
        ];
        if (!$isGuest) {
            $customer['password'] = $data->get('password');
        }

        $event = new DataMappingEvent($data, $customer, $context->getContext());
        $this->eventDispatcher->dispatch($event, CustomerEvents::MAPPING_REGISTER_CUSTOMER);

        $customer = $event->getOutput();
        $customer['id'] = Uuid::randomHex();

        return $customer;
    }

    private function getCustomerCreateValidationDefinition(bool $isGuest, DataBag $data, SalesChannelContext $context): DataValidationDefinition
    {
        $validation = $this->accountValidationFactory->create($context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('registrationSalesChannels.id', $context->getSalesChannelId()));

        $validation->add('requestedGroupId', new EntityExists([
            'entity' => 'customer_group',
            'context' => $context->getContext(),
            'criteria' => $criteria,
        ]));

        if (!$isGuest) {
            $minLength = $this->systemConfigService->get('core.loginRegistration.passwordMinLength', $context->getSalesChannelId());
            $validation->add('password', new NotBlank(), new Length(['min' => $minLength]));
            $options = ['context' => $context->getContext(), 'salesChannelContext' => $context];
            $validation->add('email', new CustomerEmailUnique($options));
        }

        $validationEvent = new BuildValidationEvent($validation, $data, $context->getContext());
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        return $validation;
    }

    private function getBoundSalesChannelId(string $email, SalesChannelContext $context): ?string
    {
        $bindCustomers = $this->systemConfigService->get('core.systemWideLoginRegistration.isCustomerBoundToSalesChannel');
        $salesChannelId = $context->getSalesChannelId();

        if ($bindCustomers) {
            return $salesChannelId;
        }

        if ($this->hasBoundAccount($email)) {
            return $salesChannelId;
        }

        return null;
    }

    private function hasBoundAccount(string $email): bool
    {
        $query = $this->connection->createQueryBuilder();

        /** @var array{email: string, guest: int, bound_sales_channel_id: string|null}[] $results */
        $results = $query
            ->select('LOWER(HEX(bound_sales_channel_id)) as bound_sales_channel_id')
            ->from('customer')
            ->where($query->expr()->eq('email', $query->createPositionalParameter($email)))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($results as $result) {
            if ($result['bound_sales_channel_id']) {
                return true;
            }
        }

        return false;
    }

    private function getConfirmUrl(SalesChannelContext $context, CustomerEntity $customer): string
    {
        $urlTemplate = $this->systemConfigService->get(
            'core.loginRegistration.confirmationUrl',
            $context->getSalesChannelId()
        );
        if (!\is_string($urlTemplate)) {
            $urlTemplate = '/registration/confirm?em=%%HASHEDEMAIL%%&hash=%%SUBSCRIBEHASH%%';
        }

        $emailHash = Hasher::hash($customer->getEmail(), 'sha1');

        $urlEvent = new CustomerConfirmRegisterUrlEvent($context, $urlTemplate, $emailHash, $customer->getHash(), $customer);
        $this->eventDispatcher->dispatch($urlEvent);

        return str_replace(
            ['%%HASHEDEMAIL%%', '%%SUBSCRIBEHASH%%'],
            [$emailHash, $customer->getHash()],
            $urlEvent->getConfirmUrl()
        );
    }

    private function getDefaultSalutationId(SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new EqualsFilter('salutationKey', SalutationDefinition::NOT_SPECIFIED)
        );

        return $this->salutationRepository->searchIds($criteria, $context->getContext())->firstId();
    }
}
