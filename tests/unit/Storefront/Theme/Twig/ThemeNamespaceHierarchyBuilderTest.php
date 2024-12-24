<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Twig;

use Cicada\Core\Checkout\Document\Event\DocumentTemplateRendererParameterEvent;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Theme\DatabaseSalesChannelThemeLoader;
use Cicada\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;
use Cicada\Storefront\Theme\Twig\ThemeNamespaceHierarchyBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $cachedThemeLoader = new DatabaseSalesChannelThemeLoader($connectionMock);

        $this->builder = new ThemeNamespaceHierarchyBuilder(new TestInheritanceBuilder(), $cachedThemeLoader);
    }

    public function testThemeNamespaceHierarchyBuilderSubscribesToRequestAndExceptionEvents(): void
    {
        $events = $this->builder->getSubscribedEvents();

        static::assertEquals([
            KernelEvents::REQUEST,
            KernelEvents::EXCEPTION,
            DocumentTemplateRendererParameterEvent::class,
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

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, bool> $expectedThemes
     */
    #[DataProvider('onRenderingDocumentProvider')]
    public function testOnRenderingDocument(array $parameters, array $expectedThemes, ?string $usingTheme): void
    {
        $request = Request::createFromGlobals();
        $event = new DocumentTemplateRendererParameterEvent($parameters);

        $expectedDB = [
            'themeName' => $usingTheme,
            'parentThemeName' => null,
            'themeId' => Uuid::randomHex(),
        ];
        $connectionMock = $this->createMock(Connection::class);
        if (\array_key_exists('context', $parameters)) {
            $connectionMock->expects(static::exactly(1))->method('fetchAssociative')->willReturn($expectedDB);
        }
        $cachedThemeLoader = new DatabaseSalesChannelThemeLoader($connectionMock);

        $builder = new ThemeNamespaceHierarchyBuilder(new TestInheritanceBuilder(), $cachedThemeLoader);

        $builder->onDocumentRendering($event);

        $this->assertThemes($expectedThemes, $builder);

        $builder = new ThemeNamespaceHierarchyBuilder(new TestInheritanceBuilder(), $cachedThemeLoader);

        $builder->requestEvent(new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException()));

        $this->assertThemes([], $builder);
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
     * @return iterable<string, array<mixed>>
     */
    public static function onRenderingDocumentProvider(): iterable
    {
        $context = Generator::createSalesChannelContext();

        yield 'no theme is using' => [
            [
                'context' => $context,
            ],
            [],
            null,
        ];

        yield 'no context in parameters' => [
            [],
            [],
            'SwagTheme',
        ];

        yield 'theme is using' => [
            [
                'context' => $context,
            ],
            [
                'SwagTheme' => true,
                'Storefront' => true,
            ],
            'SwagTheme',
        ];
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
