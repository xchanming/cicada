<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Theme\AbstractResolvedConfigLoader;
use Cicada\Storefront\Theme\ThemeConfigValueAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(ThemeConfigValueAccessor::class)]
class ThemeConfigValueAccessorTest extends TestCase
{
    public function testGetDisabledFineGrainedCaching(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);

        $themeConfigLoader = $this->createMock(AbstractResolvedConfigLoader::class);
        $themeConfigLoader->expects(static::once())
            ->method('load')
            ->willReturn(['foo' => 'bar']);

        $themeConfigValueAccessor = new ThemeConfigValueAccessor(
            $themeConfigLoader,
            false,
            new EventDispatcher()
        );

        $context = $this->createMock(SalesChannelContext::class);
        $themeId = Uuid::randomHex();

        $themeConfigValueAccessor->trace('all', function () use ($themeConfigValueAccessor, $context, $themeId): void {
            static::assertEquals(
                'bar',
                $themeConfigValueAccessor->get('foo', $context, $themeId)
            );
        });

        static::assertSame(
            [
                'cicada.theme',
            ],
            $themeConfigValueAccessor->getTrace('all')
        );
    }

    public function testGetEnabledFineGrained(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);

        $themeConfigValueAccessor = new ThemeConfigValueAccessor(
            $this->createMock(AbstractResolvedConfigLoader::class),
            true,
            new EventDispatcher()
        );

        $context = $this->createMock(SalesChannelContext::class);
        $themeId = Uuid::randomHex();

        $themeConfigValueAccessor->trace('all', function () use ($themeConfigValueAccessor, $context, $themeId): void {
            $themeConfigValueAccessor->get('foo', $context, $themeId);
        });

        static::assertSame(
            [
                'theme.foo',
            ],
            $themeConfigValueAccessor->getTrace('all')
        );
    }
}
