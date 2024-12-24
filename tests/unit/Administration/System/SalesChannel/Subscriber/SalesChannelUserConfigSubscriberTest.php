<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\System\SalesChannel\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Administration\System\SalesChannel\Subscriber\SalesChannelUserConfigSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelEvents;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigCollection;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigDefinition;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigEntity;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(SalesChannelUserConfigSubscriber::class)]
class SalesChannelUserConfigSubscriberTest extends TestCase
{
    private MockObject&EntityRepository $userConfigRepository;

    private SalesChannelUserConfigSubscriber $salesChannelUserConfigSubscriber;

    protected function setUp(): void
    {
        $this->userConfigRepository = $this->createMock(EntityRepository::class);
        $this->salesChannelUserConfigSubscriber = new SalesChannelUserConfigSubscriber($this->userConfigRepository);
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals([
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'onSalesChannelDeleted',
        ], $this->salesChannelUserConfigSubscriber->getSubscribedEvents());
    }

    public function testOnSalesChannelDeletedUpsertWithEmptyArray(): void
    {
        $context = Context::createDefaultContext();
        $event = new EntityDeletedEvent('testEntity', [], $context);

        $this->userConfigRepository->expects(static::once())
            ->method('search')
                ->willReturn(new EntitySearchResult(
                    UserConfigDefinition::ENTITY_NAME,
                    0,
                    new UserConfigCollection([]),
                    null,
                    new Criteria(),
                    $context
                ));

        $this->userConfigRepository->expects(static::once())
            ->method('upsert')
            ->with([], $context);
        $this->salesChannelUserConfigSubscriber->onSalesChannelDeleted($event);
    }

    public function testOnSalesChannelDeletedUpsertWithNoSalesChannelId(): void
    {
        $userConfig = new UserConfigEntity();
        $userConfig->setUniqueIdentifier('user-config-id');
        // $userConfig->setValue(['']);
        $context = Context::createDefaultContext();
        $event = new EntityDeletedEvent('testEntity', [], $context);

        $this->userConfigRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                UserConfigDefinition::ENTITY_NAME,
                1,
                new UserConfigCollection([$userConfig]),
                null,
                new Criteria(),
                $context
            ));

        $this->userConfigRepository->expects(static::once())
            ->method('upsert')
            ->with([], $context);
        $this->salesChannelUserConfigSubscriber->onSalesChannelDeleted($event);
    }

    public function testOnSalesChannelDeletedUpsertWithNoMatchingId(): void
    {
        $userConfig = new UserConfigEntity();
        $userConfig->setUniqueIdentifier('user-config-id');
        $userConfig->setValue(['']);
        $context = Context::createDefaultContext();
        $event = new EntityDeletedEvent('testEntity', [], $context);

        $this->userConfigRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                UserConfigDefinition::ENTITY_NAME,
                1,
                new UserConfigCollection([$userConfig]),
                null,
                new Criteria(),
                $context
            ));

        $this->userConfigRepository->expects(static::once())
            ->method('upsert')
            ->with([], $context);

        $this->salesChannelUserConfigSubscriber->onSalesChannelDeleted($event);
    }

    public function testOnSalesChannelDeletedUpsertWithMatchingId(): void
    {
        $userConfig = new UserConfigEntity();
        $userConfig->setUniqueIdentifier('user-config-id');
        $userConfig->setValue(['test-deleted']);
        $userConfig->setId('test-deleted');
        $context = Context::createDefaultContext();
        $event = new EntityDeletedEvent(
            'testEntity',
            [new EntityWriteResult(
                'test-deleted',
                [],
                UserConfigDefinition::ENTITY_NAME,
                EntityWriteResult::OPERATION_INSERT
            ),
            ],
            $context
        );

        $this->userConfigRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                UserConfigDefinition::ENTITY_NAME,
                1,
                new UserConfigCollection([$userConfig]),
                null,
                new Criteria(),
                $context
            ));

        $this->userConfigRepository->expects(static::once())
            ->method('upsert')
            ->with([['id' => 'test-deleted', 'value' => []]], $context);
        $this->salesChannelUserConfigSubscriber->onSalesChannelDeleted($event);
    }
}
