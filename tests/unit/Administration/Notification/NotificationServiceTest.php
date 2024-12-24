<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Notification;

use Cicada\Administration\Notification\NotificationCollection;
use Cicada\Administration\Notification\NotificationEntity;
use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Cicada\Core\Framework\Api\Context\ShopApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('administration')]
#[CoversClass(NotificationService::class)]
class NotificationServiceTest extends TestCase
{
    private MockObject&EntityRepository $entityRepository;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->notificationService = new NotificationService($this->entityRepository);
    }

    public function testGetNotificationExceptionWithInvalidSource(): void
    {
        $context = Context::createDefaultContext(new ShopApiSource('salesChannelId'));

        $this->expectExceptionObject(new InvalidContextSourceException(AdminApiSource::class, $context->getSource()::class));

        $this->notificationService->getNotifications($context, 0, '');
    }

    public function testCreateNotification(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user1234'));
        $notification = [
            'id' => Uuid::randomHex(),
            'status' => 'warning',
            'message' => 'test',
            'adminOnly' => true,
            'requiredPrivileges' => [],
            'createdByIntegrationId' => 'integ123',
            'createdByUserId' => 'user1234',
        ];

        $this->entityRepository->expects(static::once())
            ->method('create')
            ->with([$notification], $context);

        $this->notificationService->createNotification($notification, $context);

        static::assertSame(Context::USER_SCOPE, $context->getScope());
    }

    public function testGetNotificationWithSourceIsNotAdminWithEmptyCollection(): void
    {
        $source = new AdminApiSource('user1234');
        $source->setIsAdmin(false);
        $context = Context::createDefaultContext($source);

        $notifications = $this->notificationService->getNotifications($context, 0, '1718179529');

        static::assertEquals([
            'notifications' => new NotificationCollection(),
            'timestamp' => null,
        ], $notifications);
    }

    public function testGetNotificationWithSourceIsAdminWithNotEmptyCollection(): void
    {
        $source = new AdminApiSource('user1234');
        $source->setIsAdmin(true);
        $context = Context::createDefaultContext($source);

        $notificationCollection = new NotificationCollection();

        $notification = new NotificationEntity();
        $notification->setId(Uuid::randomHex());
        $notification->setCreatedAt(new \DateTime('2024-06-15T00:00:00.000+00:00'));

        $notificationCollection->add($notification);

        $notification = new NotificationEntity();
        $notification->setId(Uuid::randomHex());
        $notification->setCreatedAt(new \DateTime('2024-06-13T00:00:00.000+00:00'));

        $notificationCollection->add($notification);

        $this->entityRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'notification',
                2,
                $notificationCollection,
                null,
                new Criteria(),
                $context
            ));

        $notifications = $this->notificationService->getNotifications($context, 0, '1718179529');

        static::assertEquals([
            'notifications' => $notificationCollection,
            'timestamp' => '2024-06-13 00:00:00.000',
        ], $notifications);
    }

    public function testGetNotificationWithSourceIsNotAdminWithNoPermission(): void
    {
        $source = new AdminApiSource('user1234');
        $source->setIsAdmin(false);

        $context = Context::createDefaultContext($source);

        $notification = new NotificationEntity();
        $notificationCollection = new NotificationCollection();

        $notification->setId(Uuid::randomHex());
        $notification->setCreatedAt(new \DateTime('2024-06-15T00:00:00.000+00:00'));

        $notificationCollection->add($notification);

        $notification = new NotificationEntity();
        $notification->setId(Uuid::randomHex());
        $notification->setCreatedAt(new \DateTime('2024-06-20T00:00:00.000+00:00'));

        $notificationCollection->add($notification);

        $this->entityRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'notification',
                2,
                $notificationCollection,
                null,
                new Criteria(),
                $context
            ));

        $notifications = $this->notificationService->getNotifications($context, 0, '1718179529');

        static::assertEquals([
            'notifications' => $notificationCollection,
            'timestamp' => '2024-06-20 00:00:00.000',
        ], $notifications);
    }

    public function testGetNotificationWithSourceIsNotAdminWithNotEmptyCollection(): void
    {
        $source = new AdminApiSource('user1234');
        $source->setIsAdmin(false);
        $source->setPermissions(['test-read']);
        $context = Context::createDefaultContext($source);

        $notification = new NotificationEntity();
        $notificationCollection = new NotificationCollection();

        $notification->setId(Uuid::randomHex());
        $notification->setCreatedAt(new \DateTime('2024-06-15T00:00:00.000+00:00'));
        $notification->setRequiredPrivileges(['test-read']);

        $notificationCollection->add($notification);

        $notification = new NotificationEntity();
        $notification->setId('id-to-be-filtered');
        $notification->setCreatedAt(new \DateTime('2024-06-20T00:00:00.000+00:00'));
        $notification->setRequiredPrivileges(['test-read', 'test-write']);

        $notificationCollection->add($notification);

        $this->entityRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'notification',
                2,
                $notificationCollection,
                null,
                new Criteria(),
                $context
            ));

        $notifications = $this->notificationService->getNotifications($context, 0, '1718179529');

        $notificationCollection->remove('id-to-be-filtered');

        static::assertEquals([
            'notifications' => $notificationCollection,
            'timestamp' => '2024-06-20 00:00:00.000',
        ], $notifications);
    }
}
