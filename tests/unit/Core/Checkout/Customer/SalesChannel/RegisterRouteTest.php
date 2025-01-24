<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(RegisterRoute::class)]
class RegisterRouteTest extends TestCase
{
    public function testAccountType(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.passwordMinLength' => '8',
            ],
            'core.systemWideLoginRegistration.isCustomerBoundToSalesChannel' => true,
        ]);

        $result = $this->createMock(EntitySearchResult::class);
        $customerEntity = new CustomerEntity();
        $customerEntity->setDoubleOptInRegistration(false);
        $customerEntity->setId('customer-1');
        $customerEntity->setGuest(false);
        $result->method('first')->willReturn($customerEntity);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->method('search')->willReturn($result);
        $customerRepository
            ->expects(static::once())
            ->method('create')
            ->willReturnCallback(function (array $create) {
                static::assertCount(1, $create);
                static::assertArrayHasKey('accountType', $create[0]);
                static::assertSame(CustomerEntity::ACCOUNT_TYPE_PRIVATE, $create[0]['accountType']);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([]), []);
            });

        $register = new RegisterRoute(
            new EventDispatcher(),
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(DataValidationFactoryInterface::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(SalesChannelContextPersister::class),
            $this->createMock(Connection::class),
            $this->createMock(SalesChannelContextService::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $this->createMock(EntityRepository::class),
        );

        $data = [
            'email' => 'test@test.de',
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
        ];

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $register->register(new RequestDataBag($data), $salesChannelContext, false);
    }

    public function testCustomFields(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.passwordMinLength' => '8',
            ],
            'core.systemWideLoginRegistration.isCustomerBoundToSalesChannel' => true,
        ]);

        $result = $this->createMock(EntitySearchResult::class);
        $customerEntity = new CustomerEntity();
        $customerEntity->setDoubleOptInRegistration(false);
        $customerEntity->setId('customer-1');
        $customerEntity->setGuest(false);
        $result->method('first')->willReturn($customerEntity);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->method('search')->willReturn($result);
        $customerRepository
            ->expects(static::once())
            ->method('create')
            ->willReturnCallback(function (array $create) {
                static::assertSame(['mapped' => 1], $create[0]['customFields']);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([]), []);
            });

        $customFieldMapper = new StoreApiCustomFieldMapper($this->createMock(Connection::class), [
            CustomerDefinition::ENTITY_NAME => [
                ['name' => 'mapped', 'type' => 'int'],
            ],
        ]);

        $register = new RegisterRoute(
            new EventDispatcher(),
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(DataValidationFactoryInterface::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(SalesChannelContextPersister::class),
            $this->createMock(Connection::class),
            $this->createMock(SalesChannelContextService::class),
            $customFieldMapper,
            $this->createMock(EntityRepository::class),
        );

        $data = [
            'email' => 'test@test.de',
            'billingAddress' => [
                'countryId' => Uuid::randomHex(),
            ],
            'customFields' => [
                'test' => '1',
                'mapped' => '1',
            ],
        ];

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $register->register(new RequestDataBag($data), $salesChannelContext, false);
    }

    public function testSalutationIdIsAssignedDefaultValue(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.showAccountTypeSelection' => true,
                'core.loginRegistration.passwordMinLength' => '8',
            ],
            'core.systemWideLoginRegistration.isCustomerBoundToSalesChannel' => true,
        ]);

        $result = $this->createMock(EntitySearchResult::class);
        $customerEntity = new CustomerEntity();
        $customerEntity->setDoubleOptInRegistration(false);
        $customerEntity->setId('customer-1');
        $customerEntity->setGuest(false);
        $result->method('first')->willReturn($customerEntity);

        $salutationId = Uuid::randomHex();
        /** @var StaticEntityRepository<SalutationCollection> $salutationRepository */
        $salutationRepository = new StaticEntityRepository([[$salutationId]], new SalutationDefinition());

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->method('search')->willReturn($result);
        $customerRepository
            ->expects(static::once())
            ->method('create')
            ->willReturnCallback(function (array $create) use ($salutationId) {
                static::assertCount(1, $create);
                static::assertArrayHasKey('salutationId', $create[0]);
                static::assertSame($create[0]['salutationId'], $salutationId);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection([]), []);
            });

        $register = new RegisterRoute(
            new EventDispatcher(),
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(DataValidationFactoryInterface::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(SalesChannelContextPersister::class),
            $this->createMock(Connection::class),
            $this->createMock(SalesChannelContextService::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $salutationRepository,
        );

        $data = [
            'email' => 'test@test.de',
            'billingAddress' => [
                'countryId' => Uuid::randomHex(),
            ],
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'salutationId' => '',
        ];

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $register->register(new RequestDataBag($data), $salesChannelContext, false);
    }

    public function testRedirectParameters(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.passwordMinLength' => '8',
                'core.loginRegistration.doubleOptInRegistration' => true,
                'core.cart.wishlistEnabled' => true,
            ],
            'core.systemWideLoginRegistration.isCustomerBoundToSalesChannel' => true,
        ]);

        $customerEntity = new CustomerEntity();
        $customerEntity->setDoubleOptInRegistration(true);
        $customerEntity->setId('customer-1');
        $customerEntity->setGuest(false);
        $customerEntity->setEmail('test@test.de');

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository(
            [new CustomerCollection([$customerEntity])],
            new CustomerDefinition(),
        );

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(static::atLeast(1))
            ->method('dispatch')
            ->with(
                static::callback(function (Event $event): bool {
                    if ($event instanceof CustomerDoubleOptInRegistrationEvent) {
                        $query = [];
                        $queryString = \parse_url($event->getConfirmUrl(), \PHP_URL_QUERY);
                        self::assertIsString($queryString);
                        \parse_str($queryString, $query);
                        self::assertArrayHasKey('productId', $query);
                        self::assertSame('018b906b869273fea7926f161dd23911', $query['productId']);
                    }

                    return true;
                })
            );

        $register = new RegisterRoute(
            $eventDispatcher,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(DataValidationFactoryInterface::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(SalesChannelContextPersister::class),
            $this->createMock(Connection::class),
            $this->createMock(SalesChannelContextService::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $this->createMock(EntityRepository::class),
        );

        $data = [
            'email' => 'test@test.de',
            'billingAddress' => [
                'countryId' => Uuid::randomHex(),
            ],
            'storefrontUrl' => 'http://localhost:8000',
            'redirectTo' => 'frontend.wishlist.add.after.login',
            'redirectParameters' => '{"productId":"018b906b869273fea7926f161dd23911"}',
        ];

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $register->register(new RequestDataBag($data), $salesChannelContext, false);
    }

    public function testInvalidRedirectParameters(): void
    {
        $systemConfigService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.passwordMinLength' => '8',
                'core.loginRegistration.doubleOptInRegistration' => true,
                'core.cart.wishlistEnabled' => true,
            ],
            'core.systemWideLoginRegistration.isCustomerBoundToSalesChannel' => true,
        ]);

        $customerEntity = new CustomerEntity();
        $customerEntity->setDoubleOptInRegistration(true);
        $customerEntity->setId('customer-1');
        $customerEntity->setGuest(false);
        $customerEntity->setEmail('test@test.de');

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository(
            [new CustomerCollection([$customerEntity])],
            new CustomerDefinition(),
        );

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(static::atLeast(1))
            ->method('dispatch')
            ->with(
                static::callback(function ($event): bool {
                    if ($event instanceof CustomerDoubleOptInRegistrationEvent) {
                        $query = [];
                        $queryString = \parse_url($event->getConfirmUrl(), \PHP_URL_QUERY);
                        self::assertIsString($queryString);
                        \parse_str($queryString, $query);
                        self::assertArrayHasKey('redirectTo', $query);
                        self::assertSame('frontend.wishlist.add.after.login', $query['redirectTo']);
                    }

                    return true;
                })
            );

        $register = new RegisterRoute(
            $eventDispatcher,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(DataValidationFactoryInterface::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(SalesChannelContextPersister::class),
            $this->createMock(Connection::class),
            $this->createMock(SalesChannelContextService::class),
            $this->createMock(StoreApiCustomFieldMapper::class),
            $this->createMock(EntityRepository::class),
        );

        $data = [
            'email' => 'test@test.de',
            'billingAddress' => [
                'countryId' => Uuid::randomHex(),
            ],
            'storefrontUrl' => 'http://localhost:8000',
            'redirectTo' => 'frontend.wishlist.add.after.login',
            'redirectParameters' => 'thisisnotajson',
        ];

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getSalesChannelId')->willReturn(TestDefaults::SALES_CHANNEL);

        $register->register(new RequestDataBag($data), $salesChannelContext, false);
    }
}
