<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Newsletter\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Content\Newsletter\Event\NewsletterUnsubscribeEvent;
use Cicada\Core\Content\Newsletter\NewsletterException;
use Cicada\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Cicada\Core\Content\Newsletter\SalesChannel\NewsletterUnsubscribeRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(NewsletterUnsubscribeRoute::class)]
class NewsletterUnsubscribeRouteTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->salesChannelContext = Generator::createSalesChannelContext();
    }

    public function testUnsubscribe(): void
    {
        $requestData = new RequestDataBag();
        $requestData->add([
            'email' => 'test@example.com',
        ]);

        $newsletterRecipientEntity = new NewsletterRecipientEntity();
        $newsletterRecipientEntity->setId(Uuid::randomHex());
        $newsletterRecipientEntity->setEmail('test@example.com');
        $newsletterRecipientEntity->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $newsletterRecipientEntity->setConfirmedAt(new \DateTime());

        $entityRepository = new StaticEntityRepository([
            new NewsletterRecipientCollection([$newsletterRecipientEntity]),
        ]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                static::isInstanceOf(NewsletterUnsubscribeEvent::class),
            );

        $newsletterSubscribeRoute = new NewsletterUnsubscribeRoute(
            $entityRepository,
            $this->createMock(DataValidator::class),
            $eventDispatcher,
        );

        $newsletterSubscribeRoute->unsubscribe($requestData, $this->salesChannelContext);
        static::assertSame([
            [
                [
                    'email' => $newsletterRecipientEntity->getEmail(),
                    'id' => $newsletterRecipientEntity->getId(),
                    'status' => NewsletterSubscribeRoute::STATUS_OPT_OUT,
                ],
            ],
        ], $entityRepository->updates);
    }

    public function testUnsubscribeWithoutEmail(): void
    {
        $requestData = new RequestDataBag();
        $requestData->add([
            'email' => null,
        ]);

        $entityRepository = new StaticEntityRepository([]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::never())
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                static::isInstanceOf(NewsletterUnsubscribeEvent::class),
            );

        $newsletterSubscribeRoute = new NewsletterUnsubscribeRoute(
            $entityRepository,
            $this->createMock(DataValidator::class),
            $eventDispatcher,
        );

        static::expectException(NewsletterException::class);
        static::expectExceptionMessage('The email parameter is missing.');
        $newsletterSubscribeRoute->unsubscribe($requestData, $this->salesChannelContext);
    }

    public function testUnsubscribeWithNotFoundEmail(): void
    {
        $requestData = new RequestDataBag();
        $requestData->add([
            'email' => 'test@example.com',
        ]);

        $entityRepository = new StaticEntityRepository([
            new NewsletterRecipientCollection([]),
        ]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::never())
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                static::isInstanceOf(NewsletterUnsubscribeEvent::class),
            );

        $newsletterSubscribeRoute = new NewsletterUnsubscribeRoute(
            $entityRepository,
            $this->createMock(DataValidator::class),
            $eventDispatcher,
        );

        static::expectException(NewsletterException::class);
        static::expectExceptionMessage('The NewsletterRecipient with the identifier "email" - test@example.com was not found.');
        $newsletterSubscribeRoute->unsubscribe($requestData, $this->salesChannelContext);
    }
}
