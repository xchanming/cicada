<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ProductExport\Service;

use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderFooterContextEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderHeaderContextEvent;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\ProductExportException;
use Cicada\Core\Content\ProductExport\Service\ProductExportRenderer;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException;
use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(ProductExportRenderer::class)]
class ProductExportRendererTest extends TestCase
{
    private readonly SalesChannelContext $context;

    protected function setUp(): void
    {
        $this->context = Generator::generateSalesChannelContext();
    }

    #[DataProvider('renderHeaderProvider')]
    public function testRenderHeader(?string $headerTemplate, string $expected, string $domainUrl = 'http://de.test'): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setHeaderTemplate($headerTemplate);

        $domain = new SalesChannelDomainEntity();
        $domain->setUrl($domainUrl);

        $productExport->setSalesChannelDomain($domain);

        $event = new ProductExportRenderHeaderContextEvent(
            [
                'productExport' => $productExport,
                'context' => $this->context,
            ]
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturn($event);

        $environment = new Environment(new ArrayLoader());

        $twigRenderer = new StringTemplateRenderer($environment, sys_get_temp_dir());
        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->method('replace')->with($expected, $domainUrl, $this->context)->willReturn($expected);

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        $rendered = $renderer->renderHeader($productExport, $this->context);

        static::assertEquals($expected, $rendered);
    }

    public function testRenderHeaderError(): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setHeaderTemplate('content');

        $event = new ProductExportRenderHeaderContextEvent(
            [
                'productExport' => $productExport,
                'context' => $this->context,
            ]
        );
        $loggingEvent = new ProductExportLoggingEvent(
            $this->context->getContext(),
            'error',
            Level::Warning,
            ProductExportException::renderHeaderException('error')
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(static::exactly(2))->method('dispatch')->willReturnOnConsecutiveCalls($event, $loggingEvent);

        $twigRenderer = $this->createMock(StringTemplateRenderer::class);
        $twigRenderer->expects(static::once())->method('render')->willThrowException(new StringTemplateRenderingException('error'));

        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->expects(static::never())->method('replace');

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        static::expectException(ProductExportException::class);
        static::expectExceptionMessage('error');

        $renderer->renderHeader($productExport, $this->context);
    }

    #[DataProvider('renderHeaderProvider')]
    public function testRenderFooter(?string $footerTemplate, string $expected, string $domainUrl = 'http://de.test'): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setFooterTemplate($footerTemplate);

        $domain = new SalesChannelDomainEntity();
        $domain->setUrl($domainUrl);

        $productExport->setSalesChannelDomain($domain);

        $event = new ProductExportRenderFooterContextEvent(
            [
                'productExport' => $productExport,
                'context' => $this->context,
            ]
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturn($event);

        $environment = new Environment(new ArrayLoader());

        $twigRenderer = new StringTemplateRenderer($environment, sys_get_temp_dir());
        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->method('replace')->with($expected, $domainUrl, $this->context)->willReturn($expected);

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        $rendered = $renderer->renderFooter($productExport, $this->context);

        static::assertEquals($expected, $rendered);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('renderBodyProvider')]
    public function testRenderBody(?string $bodyTemplate, string $expected, array $data, string $domainUrl = 'http://de.test'): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setBodyTemplate($bodyTemplate);

        $domain = new SalesChannelDomainEntity();
        $domain->setUrl($domainUrl);

        $productExport->setSalesChannelDomain($domain);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $environment = new Environment(new ArrayLoader());

        $twigRenderer = new StringTemplateRenderer($environment, sys_get_temp_dir());
        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->method('replace')->with($expected, $domainUrl, $this->context)->willReturn($expected);

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        $rendered = $renderer->renderBody($productExport, $this->context, $data);

        static::assertEquals($expected, $rendered);
    }

    public function testRenderFooterError(): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setFooterTemplate('content');

        $event = new ProductExportRenderFooterContextEvent(
            [
                'productExport' => $productExport,
                'context' => $this->context,
            ]
        );
        $loggingEvent = new ProductExportLoggingEvent(
            $this->context->getContext(),
            'error',
            Level::Warning,
            ProductExportException::renderProductException('error')
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(static::exactly(2))->method('dispatch')->willReturnOnConsecutiveCalls($event, $loggingEvent);

        $twigRenderer = $this->createMock(StringTemplateRenderer::class);
        $twigRenderer->expects(static::once())->method('render')->willThrowException(new StringTemplateRenderingException('error'));

        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->expects(static::never())->method('replace');

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        static::expectException(ProductExportException::class);
        static::expectExceptionMessage('error');

        $renderer->renderFooter($productExport, $this->context);
    }

    public function testRenderEmptyBody(): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setBodyTemplate(null);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $twigRenderer = $this->createMock(StringTemplateRenderer::class);
        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        static::expectException(ProductExportException::class);
        static::expectExceptionMessage('Template body not set');

        $renderer->renderBody($productExport, $this->context, []);
    }

    public function testRenderBodyError(): void
    {
        $productExport = new ProductExportEntity();
        $productExport->setId(Uuid::randomHex());
        $productExport->setBodyTemplate('content');

        $loggingEvent = new ProductExportLoggingEvent(
            $this->context->getContext(),
            'error',
            Level::Warning,
            ProductExportException::renderProductException('error')
        );

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(static::once())->method('dispatch')->willReturn($loggingEvent);

        $twigRenderer = $this->createMock(StringTemplateRenderer::class);
        $twigRenderer->expects(static::once())->method('render')->willThrowException(new StringTemplateRenderingException('error'));

        $seoUrlPlaceholderHandler = $this->createMock(SeoUrlPlaceholderHandlerInterface::class);
        $seoUrlPlaceholderHandler->expects(static::never())->method('replace');

        $renderer = new ProductExportRenderer(
            $twigRenderer,
            $dispatcher,
            $seoUrlPlaceholderHandler,
        );

        static::expectException(ProductExportException::class);
        static::expectExceptionMessage('error');

        $renderer->renderBody($productExport, $this->context, []);
    }

    /**
     * @return iterable<string, array<int, string|null>>
     */
    public static function renderHeaderProvider(): iterable
    {
        yield 'null' => [
            null,
            '',
        ];
        yield 'empty' => [
            '',
            \PHP_EOL,
        ];
        yield 'plain' => [
            'this is a plain string',
            'this is a plain string' . \PHP_EOL,
        ];

        yield 'with domain url in template' => [
            'this is a with http://en.test in template',
            'this is a with http://en.test in template' . \PHP_EOL,
            'http://en.test',
        ];
    }

    /**
     * @return iterable<string, array<int, string|array<string, mixed>|null>>
     */
    public static function renderBodyProvider(): iterable
    {
        yield 'empty' => [
            '',
            \PHP_EOL,
            [],
        ];

        yield 'plain' => [
            'this is a plain string',
            'this is a plain string' . \PHP_EOL, [],
        ];

        yield 'with correct domain url in template' => [
            'this is a with http://de.test in template',
            'this is a with http://de.test in template' . \PHP_EOL,
            [],
            'http://en.test',
        ];
    }
}
