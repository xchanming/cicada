<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Twig;

use Cicada\Core\Checkout\Document\Event\DocumentTemplateRendererParameterEvent;
use Cicada\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Adapter\Twig\TemplateFinder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(DocumentTemplateRenderer::class)]
class DocumentTemplateRendererTest extends TestCase
{
    private static bool $rendererParameterEventCalled = false;

    public function testDocumentTemplateRendererParameterEventIsDispatched(): void
    {
        $templateFinder = $this->createMock(TemplateFinder::class);
        $templateFinder->expects(static::once())->method('reset');
        $templateFinder->expects(static::once())->method('find')->willReturnCallback(function (string $template): string {
            static::assertTrue(self::$rendererParameterEventCalled, 'Expected DocumentTemplateRendererParameterEvent being thrown before TemplateFinder is called to ensure that the TemplateFinder is configured correctly');

            return $template;
        });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(DocumentTemplateRendererParameterEvent::class))
            ->willReturnCallback(function (DocumentTemplateRendererParameterEvent $event) {
                static::assertFalse(self::$rendererParameterEventCalled);
                self::$rendererParameterEventCalled = true;

                return $event;
            });

        $documentTemplateRenderer = new DocumentTemplateRenderer(
            $templateFinder,
            $this->createMock(Environment::class),
            $this->createMock(Translator::class),
            $this->createMock(SalesChannelContextFactory::class),
            $eventDispatcher,
        );

        $salesChannelId = Uuid::randomHex();
        $documentTemplateRenderer->render('view', [], Context::createDefaultContext(), $salesChannelId, Uuid::randomHex(), 'en-GB');
    }
}
