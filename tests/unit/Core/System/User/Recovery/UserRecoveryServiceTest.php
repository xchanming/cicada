<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\User\Recovery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryCollection;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryDefinition;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryEntity;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;
use Cicada\Core\System\User\Recovery\UserRecoveryService;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserDefinition;
use Cicada\Core\System\User\UserEntity;
use Cicada\Core\System\User\UserException;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(UserRecoveryService::class)]
class UserRecoveryServiceTest extends TestCase
{
    private RouterInterface&MockObject $router;

    private EventDispatcherInterface&MockObject $dispatcher;

    private SalesChannelContextService&MockObject $salesChannelContextService;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->salesChannelContextService = $this->createMock(SalesChannelContextService::class);
    }

    public function testGenerateUserRecoveryUserNotFound(): void
    {
        $userEmail = 'nonexistent@example.com';
        $context = new Context(new SystemSource(), [], Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM]);
        $recoveryEntity = new UserRecoveryEntity();
        $recoveryEntity->setUniqueIdentifier(Uuid::randomHex());

        /** @var StaticEntityRepository<UserCollection> $userRepository */
        $userRepository = new StaticEntityRepository([
            new UserCollection([]),
        ], new UserDefinition());

        /** @var StaticEntityRepository<UserRecoveryCollection> $recoveryRepository */
        $recoveryRepository = new StaticEntityRepository([
            new UserRecoveryCollection([$recoveryEntity]),
            new UserRecoveryCollection([$recoveryEntity]),
        ], new UserRecoveryDefinition());

        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([
            new SalesChannelCollection([]),
        ], new SalesChannelDefinition());

        $this->dispatcher
            ->expects(static::never())
            ->method('dispatch');

        $service = new UserRecoveryService(
            $recoveryRepository,
            $userRepository,
            $this->router,
            $this->dispatcher,
            $this->salesChannelContextService,
            $salesChannelRepository
        );

        $service->generateUserRecovery($userEmail, $context);
        static::assertCount(0, $recoveryRepository->creates);
        static::assertCount(0, $recoveryRepository->deletes);
    }

    public function testGenerateUserRecoveryWithNoSalesChannel(): void
    {
        static::expectException(UserException::class);
        static::expectExceptionMessage('No sales channel found.');

        $userEmail = 'existing@example.com';
        $context = new Context(new SystemSource(), [], Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM]);
        $user = new UserEntity();
        $user->setUniqueIdentifier(Uuid::randomHex());
        $user->setId(Uuid::randomHex());

        $recoveryEntity = new UserRecoveryEntity();
        $recoveryEntity->setUniqueIdentifier(Uuid::randomHex());
        $recoveryEntity->setId(Uuid::randomHex());
        $recoveryEntity->setHash(Uuid::randomHex());

        /** @var StaticEntityRepository<UserCollection> $userRepository */
        $userRepository = new StaticEntityRepository([
            new UserCollection([$user]),
        ], new UserDefinition());

        /** @var StaticEntityRepository<UserRecoveryCollection> $recoveryRepository */
        $recoveryRepository = new StaticEntityRepository([
            new UserRecoveryCollection([$recoveryEntity]),
            new UserRecoveryCollection([$recoveryEntity]),
        ], new UserRecoveryDefinition());

        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([
            new SalesChannelCollection([]),
        ], new SalesChannelDefinition());

        $this->router
            ->expects(static::once())
            ->method('generate')
            ->willReturn('http://example.com');

        $this->dispatcher
            ->expects(static::never())
            ->method('dispatch');

        $service = new UserRecoveryService(
            $recoveryRepository,
            $userRepository,
            $this->router,
            $this->dispatcher,
            $this->salesChannelContextService,
            $salesChannelRepository
        );

        $service->generateUserRecovery($userEmail, $context);
        static::assertCount(0, $recoveryRepository->creates);
        static::assertCount(0, $recoveryRepository->deletes);
    }

    public function testGenerateUserRecoveryWithExistingRecovery(): void
    {
        $userEmail = 'existing@example.com';
        $context = new Context(new SystemSource(), [], Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM]);
        $user = new UserEntity();
        $recoveryEntity = new UserRecoveryEntity();
        $user->setUniqueIdentifier(Uuid::randomHex());
        $user->setId(Uuid::randomHex());
        $recoveryEntity->setUniqueIdentifier(Uuid::randomHex());
        $recoveryEntity->setId(Uuid::randomHex());
        $recoveryEntity->setHash(Uuid::randomHex());
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setUniqueIdentifier(Uuid::randomHex());
        $salesChannelEntity->setId(Uuid::randomHex());
        $salesChannelEntity->setLanguageId(Uuid::randomHex());
        $salesChannelEntity->setCurrencyId(Uuid::randomHex());

        /** @var StaticEntityRepository<UserCollection> $userRepository */
        $userRepository = new StaticEntityRepository([
            new UserCollection([$user]),
        ], new UserDefinition());

        /** @var StaticEntityRepository<UserRecoveryCollection> $recoveryRepository */
        $recoveryRepository = new StaticEntityRepository([
            new UserRecoveryCollection([$recoveryEntity]),
            new UserRecoveryCollection([$recoveryEntity]),
        ], new UserRecoveryDefinition());

        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([
            new SalesChannelCollection([$salesChannelEntity]),
        ], new SalesChannelDefinition());

        $this->router
            ->expects(static::once())
            ->method('generate')
            ->willReturn('http://example.com');

        $this->salesChannelContextService
            ->expects(static::once())
            ->method('get')
            ->willReturn($this->createMock(SalesChannelContext::class));

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(
                static::isInstanceOf(UserRecoveryRequestEvent::class),
                UserRecoveryRequestEvent::EVENT_NAME
            );

        $service = new UserRecoveryService(
            $recoveryRepository,
            $userRepository,
            $this->router,
            $this->dispatcher,
            $this->salesChannelContextService,
            $salesChannelRepository
        );

        $service->generateUserRecovery($userEmail, $context);
        static::assertCount(1, $recoveryRepository->deletes);
        static::assertCount(1, $recoveryRepository->creates);
    }

    public function testGenerateUserRecoveryWithoutExistingRecovery(): void
    {
        $userEmail = 'existing@example.com';
        $context = new Context(new SystemSource(), [], Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM]);
        $user = new UserEntity();
        $recoveryEntity = new UserRecoveryEntity();
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setUniqueIdentifier(Uuid::randomHex());
        $salesChannelEntity->setId(Uuid::randomHex());
        $salesChannelEntity->setLanguageId(Uuid::randomHex());
        $salesChannelEntity->setCurrencyId(Uuid::randomHex());
        $user->setUniqueIdentifier(Uuid::randomHex());
        $user->setId(Uuid::randomHex());
        $recoveryEntity->setUniqueIdentifier(Uuid::randomHex());
        $recoveryEntity->setHash(Uuid::randomHex());

        /** @var StaticEntityRepository<UserCollection> $userRepository */
        $userRepository = new StaticEntityRepository([
            new UserCollection([$user]),
        ], new UserDefinition());

        /** @var StaticEntityRepository<UserRecoveryCollection> $recoveryRepository */
        $recoveryRepository = new StaticEntityRepository([
            new UserRecoveryCollection([]),
            new UserRecoveryCollection([$recoveryEntity]),
        ], new UserRecoveryDefinition());

        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([
            static function (Criteria $criteria, Context $context) use ($salesChannelEntity) {
                static::assertCount(1, $criteria->getFilters());
                static::assertEquals([
                    new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON)]),
                ], $criteria->getFilters());

                return new SalesChannelCollection([$salesChannelEntity]);
            },
        ], new SalesChannelDefinition());

        $this->router
            ->expects(static::once())
            ->method('generate')
            ->willReturn('http://example.com');

        $this->salesChannelContextService
            ->expects(static::once())
            ->method('get')
            ->willReturn($this->createMock(SalesChannelContext::class));

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(
                static::isInstanceOf(UserRecoveryRequestEvent::class),
                UserRecoveryRequestEvent::EVENT_NAME
            );

        $service = new UserRecoveryService(
            $recoveryRepository,
            $userRepository,
            $this->router,
            $this->dispatcher,
            $this->salesChannelContextService,
            $salesChannelRepository
        );

        $service->generateUserRecovery($userEmail, $context);
        static::assertCount(0, $recoveryRepository->deletes);
        static::assertCount(1, $recoveryRepository->creates);
    }
}
