<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Subscriber;

use Cicada\Core\Content\Media\Event\UnusedMediaSearchEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Storefront\Theme\Subscriber\UnusedMediaSubscriber;
use Cicada\Storefront\Theme\ThemeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UnusedMediaSubscriber::class)]
class UnusedMediaSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertEquals(
            [
                UnusedMediaSearchEvent::class => 'removeUsedMedia',
            ],
            UnusedMediaSubscriber::getSubscribedEvents()
        );
    }

    public function testUsedThemeMediaIdsAreRemoved(): void
    {
        $themeId1 = Uuid::randomHex();
        $themeId2 = Uuid::randomHex();

        $mediaId1 = Uuid::randomHex();
        $mediaId2 = Uuid::randomHex();
        $mediaId3 = Uuid::randomHex();
        $mediaId4 = Uuid::randomHex();
        $mediaId5 = Uuid::randomHex();

        $themeConfig1 = [
            'fields' => [
                ['type' => 'media', 'value' => $mediaId1],
            ],
        ];
        $themeConfig2 = [
            'fields' => [
                ['type' => 'media', 'value' => $mediaId2],
                ['type' => 'media', 'value' => $mediaId3],
            ],
        ];

        $themeRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($themeId1, $themeId2) {
                return new IdSearchResult(2, [['primaryKey' => $themeId1, 'data' => []], ['primaryKey' => $themeId2, 'data' => []]], $criteria, $context);
            },
        ]);

        $themeConfigMap = [
            $themeId1 => $themeConfig1,
            $themeId2 => $themeConfig2,
        ];

        $themeService = $this->createMock(ThemeService::class);
        $themeService->expects(static::exactly(2))
            ->method('getThemeConfiguration')
            ->willReturnCallback(function (string $themeId, ...$params) use ($themeConfigMap) {
                return $themeConfigMap[$themeId];
            });

        $event = new UnusedMediaSearchEvent([$mediaId1, $mediaId2, $mediaId3, $mediaId4, $mediaId5]);
        $listener = new UnusedMediaSubscriber($themeRepository, $themeService);
        $listener->removeUsedMedia($event);

        static::assertEquals([$mediaId4, $mediaId5], $event->getUnusedIds());
    }
}
