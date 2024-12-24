<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Checkout\Customer\Exception\BadCredentialsException;
use Cicada\Core\Checkout\Customer\Exception\PasswordPoliciesUpdatedException;
use Cicada\Core\Checkout\Customer\Password\LegacyPasswordVerifier;
use Cicada\Core\Checkout\Customer\SalesChannel\AbstractSwitchDefaultAddressRoute;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\System\SalesChannel\Context\CartRestorer;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AccountService::class)]
class AccountServiceTest extends TestCase
{
    public function testLoginByValidCredentials(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);
        $customer->setActive(true);
        $customer->setGuest(false);
        $customer->setPassword(TestDefaults::HASHED_PASSWORD);
        $customer->setEmail('foo@bar.de');
        $customer->setDoubleOptInRegistration(false);

        /** @var StaticEntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = new StaticEntityRepository([
            new EntitySearchResult(
                CustomerDefinition::ENTITY_NAME,
                1,
                new CustomerCollection([$customer]),
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            ),
        ]);

        $loggedinSalesChannelContext = Generator::createSalesChannelContext();
        $cartRestorer = $this->createMock(CartRestorer::class);
        $cartRestorer->expects(static::once())
            ->method('restore')
            ->willReturn($loggedinSalesChannelContext);

        $beforeLoginEventCalled = false;
        $loginEventCalled = false;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            CustomerBeforeLoginEvent::class,
            function (CustomerBeforeLoginEvent $event) use ($salesChannelContext, &$beforeLoginEventCalled): void {
                $beforeLoginEventCalled = true;
                static::assertSame('foo@bar.de', $event->getEmail());
                static::assertSame($salesChannelContext, $event->getSalesChannelContext());
            },
        );

        $eventDispatcher->addListener(
            CustomerLoginEvent::class,
            function (CustomerLoginEvent $event) use ($customer, $loggedinSalesChannelContext, &$loginEventCalled): void {
                $loginEventCalled = true;
                static::assertSame($customer, $event->getCustomer());
                static::assertSame($loggedinSalesChannelContext, $event->getSalesChannelContext());
                static::assertSame($loggedinSalesChannelContext->getToken(), $event->getContextToken());
            },
        );

        $accountService = new AccountService(
            $customerRepository,
            $eventDispatcher,
            $this->createMock(LegacyPasswordVerifier::class),
            $this->createMock(AbstractSwitchDefaultAddressRoute::class),
            $cartRestorer,
        );

        $token = $accountService->loginByCredentials('foo@bar.de', 'cicada', $salesChannelContext);
        static::assertSame($loggedinSalesChannelContext->getToken(), $token);
        static::assertTrue($beforeLoginEventCalled);
        static::assertTrue($loginEventCalled);
        static::assertCount(1, $customerRepository->updates);
        static::assertCount(1, $customerRepository->updates[0]);
        static::assertIsArray($customerRepository->updates[0][0]);
        static::assertCount(2, $customerRepository->updates[0][0]);
        static::assertSame($customer->getId(), $customerRepository->updates[0][0]['id']);
        static::assertInstanceOf(\DateTimeImmutable::class, $customerRepository->updates[0][0]['lastLogin']);
    }

    public function testLoginFailsByInvalidCredentials(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);
        $customer->setActive(true);
        $customer->setGuest(false);
        $customer->setPassword(TestDefaults::HASHED_PASSWORD);
        $customer->setEmail('foo@bar.de');
        $customer->setDoubleOptInRegistration(false);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                CustomerDefinition::ENTITY_NAME,
                1,
                new CustomerCollection([$customer]),
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            ));

        $cartRestorer = $this->createMock(CartRestorer::class);
        $cartRestorer->expects(static::never())
            ->method('restore');

        $accountService = new AccountService(
            $customerRepository,
            new EventDispatcher(),
            $this->createMock(LegacyPasswordVerifier::class),
            $this->createMock(AbstractSwitchDefaultAddressRoute::class),
            $cartRestorer,
        );

        $this->expectException(BadCredentialsException::class);
        $accountService->loginByCredentials('foo@bar.de', 'invalidPassword', $salesChannelContext);
    }

    public function testGetCustomerByIdThrowsPasswordPoliciesChangedException(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);
        $customer->setActive(true);
        $customer->setGuest(false);
        $customer->setLegacyPassword('foo');
        $customer->setLegacyEncoder('bar');

        $legacyPasswordVerifier = $this->createMock(LegacyPasswordVerifier::class);
        $legacyPasswordVerifier->expects(static::once())
            ->method('verify')
            ->with('password', $customer)
            ->willReturn(true);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                CustomerDefinition::ENTITY_NAME,
                1,
                new CustomerCollection([$customer]),
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            ));

        $exception = new WriteConstraintViolationException(new ConstraintViolationList([new ConstraintViolation('', '', [], '', '/password', '')]), '/');
        $writeException = new WriteException();
        $writeException->add($exception);

        $customerRepository->expects(static::once())
            ->method('update')
            ->with([[
                'id' => $customer->getId(),
                'password' => 'password',
                'legacyPassword' => null,
                'legacyEncoder' => null,
            ]], $salesChannelContext->getContext())
            ->willThrowException($writeException);

        $accountService = new AccountService(
            $customerRepository,
            $this->createMock(EventDispatcherInterface::class),
            $legacyPasswordVerifier,
            $this->createMock(AbstractSwitchDefaultAddressRoute::class),
            $this->createMock(CartRestorer::class),
        );

        $this->expectException(PasswordPoliciesUpdatedException::class);
        $this->expectExceptionMessage('Password policies updated.');
        $accountService->getCustomerByLogin('user', 'password', $salesChannelContext);
    }

    public function testGetCustomerByIdIgnoresOtherWriteViolations(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);
        $customer->setActive(true);
        $customer->setGuest(false);
        $customer->setLegacyPassword('foo');
        $customer->setLegacyEncoder('bar');

        $legacyPasswordVerifier = $this->createMock(LegacyPasswordVerifier::class);
        $legacyPasswordVerifier->expects(static::once())
            ->method('verify')
            ->with('password', $customer)
            ->willReturn(true);

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                CustomerDefinition::ENTITY_NAME,
                1,
                new CustomerCollection([$customer]),
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            ));

        $exception = CustomerException::badCredentials();
        $writeException = new WriteException();
        $writeException->add($exception);

        $customerRepository->expects(static::once())
            ->method('update')
            ->with([[
                'id' => $customer->getId(),
                'password' => 'password',
                'legacyPassword' => null,
                'legacyEncoder' => null,
            ]], $salesChannelContext->getContext())
            ->willThrowException($writeException);

        $accountService = new AccountService(
            $customerRepository,
            $this->createMock(EventDispatcherInterface::class),
            $legacyPasswordVerifier,
            $this->createMock(AbstractSwitchDefaultAddressRoute::class),
            $this->createMock(CartRestorer::class),
        );

        $this->expectException(WriteException::class);
        $accountService->getCustomerByLogin('user', 'password', $salesChannelContext);
    }
}
