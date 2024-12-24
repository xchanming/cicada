<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\DocumentException;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\AbstractDocumentRenderer;
use Cicada\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Cicada\Core\Checkout\Document\Renderer\DocumentRendererRegistry;
use Cicada\Core\Checkout\Document\Renderer\RendererResult;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Service\PdfRenderer;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Extensions\ExtensionDispatcher;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(DocumentGenerator::class)]
#[Package('checkout')]
class DocumentGeneratorTest extends TestCase
{
    public function testPreviewErrorThrowsDocumentException(): void
    {
        $operation = new DocumentGenerateOperation(
            'orderId',
            FileTypes::PDF,
            [],
            null,
            false,
            true
        );
        $context = Context::createDefaultContext();

        $result = new RendererResult();
        $result->addError('orderId', new \Exception('Some Error Message.'));

        $mockRenderer = $this->createMock(AbstractDocumentRenderer::class);
        $mockRenderer->method('supports')->willReturn('invoice');
        $mockRenderer
            ->expects(static::once())
            ->method('render')
            ->with(
                ['orderId' => $operation],
                $context,
                static::callback(fn (DocumentRendererConfig $config): bool => $config->deepLinkCode === 'deepLinkCode')
            )
            ->willReturn($result);

        $registry = new DocumentRendererRegistry([$mockRenderer]);
        $generator = new DocumentGenerator(
            $registry,
            new PdfRenderer([], new ExtensionDispatcher(new EventDispatcher())),
            $this->createMock(MediaService::class),
            new StaticEntityRepository([]),
            $this->createMock(Connection::class),
        );

        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('Unable to generate document. Some Error Message.');

        $generator->preview('invoice', $operation, 'deepLinkCode', $context);
    }
}
