<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Framework\Routing\CachedDomainLoader;
use Cicada\Storefront\Theme\CachedResolvedConfigLoaderInvalidator;
use Cicada\Storefront\Theme\Event\ThemeAssignedEvent;
use Cicada\Storefront\Theme\Event\ThemeConfigChangedEvent;
use Cicada\Storefront\Theme\Event\ThemeConfigResetEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CachedResolvedConfigLoaderInvalidator::class)]
class CachedResolvedConfigLoaderInvalidatorTest extends TestCase
{
    private CachedResolvedConfigLoaderInvalidator $cachedResolvedConfigLoaderInvalidator;

    private MockedCacheInvalidator $cacheInvalidator;

    protected function setUp(): void
    {
        $this->cacheInvalidator = new MockedCacheInvalidator();
        $this->cachedResolvedConfigLoaderInvalidator = new CachedResolvedConfigLoaderInvalidator($this->cacheInvalidator, true);
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals(
            [
                ThemeConfigChangedEvent::class => 'invalidate',
                ThemeAssignedEvent::class => 'assigned',
                ThemeConfigResetEvent::class => 'reset',
            ],
            CachedResolvedConfigLoaderInvalidator::getSubscribedEvents()
        );
    }

    public function testAssigned(): void
    {
        $themeId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $event = new ThemeAssignedEvent($themeId, $salesChannelId);
        $name = 'theme-config-' . $themeId;

        $expectedInvalidatedTags = [
            $name,
            CachedDomainLoader::CACHE_KEY,
            'translation.catalog.' . $salesChannelId,
        ];

        $this->cachedResolvedConfigLoaderInvalidator->assigned($event);

        if (Feature::isActive('cache_rework')) {
            $expectedInvalidatedTags = [
                $name,
                CachedDomainLoader::CACHE_KEY,
                Translator::tag($salesChannelId),
            ];
        }

        static::assertEquals(
            $expectedInvalidatedTags,
            $this->cacheInvalidator->getInvalidatedTags()
        );
    }

    public function testInvalidate(): void
    {
        $themeId = Uuid::randomHex();
        $event = new ThemeConfigChangedEvent($themeId, ['test' => 'test']);

        $expectedInvalidatedTags = [
            'theme-config-' . $themeId,
            'theme.test',
        ];

        $this->cachedResolvedConfigLoaderInvalidator->invalidate($event);

        if (Feature::isActive('cache_rework')) {
            $expectedInvalidatedTags = ['theme-config-' . $themeId];
        }

        static::assertEquals(
            $expectedInvalidatedTags,
            $this->cacheInvalidator->getInvalidatedTags()
        );
    }

    public function testInvalidateDisabledFineGrained(): void
    {
        $this->cachedResolvedConfigLoaderInvalidator = new CachedResolvedConfigLoaderInvalidator($this->cacheInvalidator, false);

        $themeId = Uuid::randomHex();
        $event = new ThemeConfigChangedEvent($themeId, ['test' => 'test']);

        $expectedInvalidatedTags = [
            'cicada.theme',
        ];

        $this->cachedResolvedConfigLoaderInvalidator->invalidate($event);

        if (Feature::isActive('cache_rework')) {
            $expectedInvalidatedTags = ['theme-config-' . $themeId];
        }

        static::assertEquals(
            $expectedInvalidatedTags,
            $this->cacheInvalidator->getInvalidatedTags()
        );
    }
}
