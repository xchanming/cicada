<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Twig;

use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\SalesChannelRequest;
use Cicada\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;
use Cicada\Storefront\Theme\Twig\ThemeNamespaceHierarchyBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[CoversClass(ThemeNamespaceHierarchyBuilder::class)]
class ThemeNamespaceHierarchyBuilderTest extends TestCase
{
    private ThemeNamespaceHierarchyBuilder $builder;

    protected function setUp(): void
    {
        $connectionMock = $this->createMock(Connection::class);

        $this->builder = new ThemeNamespaceHierarchyBuilder(new TestInheritanceBuilder());
    }

    public function testThemeNamespaceHierarchyBuilderSubscribesToRequestAndExceptionEvents(): void
    {
        $events = $this->builder->getSubscribedEvents();

        static::assertEquals([
            KernelEvents::REQUEST,
            KernelEvents::EXCEPTION,
        ], array_keys($events));
    }

    public function testThemesAreEmptyIfRequestHasNoValidAttributes(): void
    {
        $request = Request::createFromGlobals();

        $this->builder->requestEvent(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $this->assertThemes([], $this->builder);
    }

    public function testThemesIfThemeNameIsSet(): void
    {
        $request = Request::createFromGlobals();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'TestTheme');

        $this->builder->requestEvent(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $this->assertThemes([
            'Storefront' => true,
            'TestTheme' => true,
        ], $this->builder);
    }

    public function testRequestEventWithExceptionEvent(): void
    {
        $request = Request::createFromGlobals();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'TestTheme');

        $this->builder->requestEvent(new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException()));

        $this->assertThemes([
            'Storefront' => true,
            'TestTheme' => true,
        ], $this->builder);
    }

    public function testThemesIfBaseNameIsSet(): void
    {
        $request = Request::createFromGlobals();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, null);
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_BASE_NAME, 'TestTheme');

        $this->builder->requestEvent(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $this->assertThemes([
            'Storefront' => true,
            'TestTheme' => true,
        ], $this->builder);
    }

    public function testReset(): void
    {
        $request = Request::createFromGlobals();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, null);
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_BASE_NAME, 'TestTheme');

        $this->builder->requestEvent(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $this->builder->reset();

        $this->assertThemes([], $this->builder);
    }

    public function testItReturnsItsInputIfNoThemesAreSet(): void
    {
        $bundles = ['a', 'b'];

        $hierarchy = $this->builder->buildNamespaceHierarchy(['a', 'b']);

        static::assertEquals($bundles, $hierarchy);
    }

    public function testItPassesBundlesAndThemesToBuilder(): void
    {
        $bundles = ['a', 'b'];

        $request = Request::createFromGlobals();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'TestTheme');

        $this->builder->requestEvent(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $hierarchy = $this->builder->buildNamespaceHierarchy($bundles);

        static::assertEquals([
            'Storefront' => true,
            'TestTheme' => true,
        ], $hierarchy);
    }

    /**
     * @param array<string, bool> $expectation
     */
    private function assertThemes(array $expectation, ThemeNamespaceHierarchyBuilder $builder): void
    {
        $refProperty = ReflectionHelper::getPropertyValue($builder, 'themes');

        static::assertEquals($expectation, $refProperty);
    }
}

/**
 * @internal
 */
class TestInheritanceBuilder implements ThemeInheritanceBuilderInterface
{
    /**
     * @param array<string> $bundles
     * @param array<string> $themes
     *
     * @return array<string>
     */
    public function build(array $bundles, array $themes): array
    {
        return $themes;
    }
}
