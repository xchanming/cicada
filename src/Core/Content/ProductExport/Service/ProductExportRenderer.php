<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Service;

use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderFooterContextEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderHeaderContextEvent;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\ProductExportException;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException;
use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Monolog\Level;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('inventory')]
class ProductExportRenderer implements ProductExportRendererInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly StringTemplateRenderer $templateRenderer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler
    ) {
    }

    public function renderHeader(
        ProductExportEntity $productExport,
        SalesChannelContext $salesChannelContext
    ): string {
        if ($productExport->getHeaderTemplate() === null) {
            return '';
        }

        $headerContext = $this->eventDispatcher->dispatch(
            new ProductExportRenderHeaderContextEvent(
                [
                    'productExport' => $productExport,
                    'context' => $salesChannelContext,
                ]
            )
        );

        try {
            $content = $this->templateRenderer->render(
                $productExport->getHeaderTemplate(),
                $headerContext->getContext(),
                $salesChannelContext->getContext()
            ) . \PHP_EOL;

            return $this->replaceSeoUrlPlaceholder($content, $productExport, $salesChannelContext);
        } catch (StringTemplateRenderingException $exception) {
            $renderHeaderException = ProductExportException::renderHeaderException($exception->getMessage());
            $this->logException($salesChannelContext->getContext(), $renderHeaderException);

            throw $renderHeaderException;
        }
    }

    public function renderFooter(
        ProductExportEntity $productExport,
        SalesChannelContext $salesChannelContext
    ): string {
        if ($productExport->getFooterTemplate() === null) {
            return '';
        }

        $footerContext = $this->eventDispatcher->dispatch(
            new ProductExportRenderFooterContextEvent(
                [
                    'productExport' => $productExport,
                    'context' => $salesChannelContext,
                ]
            )
        );

        try {
            $content = $this->templateRenderer->render(
                $productExport->getFooterTemplate(),
                $footerContext->getContext(),
                $salesChannelContext->getContext()
            ) . \PHP_EOL;

            return $this->replaceSeoUrlPlaceholder($content, $productExport, $salesChannelContext);
        } catch (StringTemplateRenderingException $exception) {
            $renderFooterException = ProductExportException::renderFooterException($exception->getMessage());
            $this->logException($salesChannelContext->getContext(), $renderFooterException);

            throw $renderFooterException;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderBody(
        ProductExportEntity $productExport,
        SalesChannelContext $salesChannelContext,
        array $data
    ): string {
        $bodyTemplate = $productExport->getBodyTemplate();
        if (!\is_string($bodyTemplate)) {
            throw ProductExportException::templateBodyNotSet();
        }

        try {
            $content = $this->templateRenderer->render(
                $bodyTemplate,
                $data,
                $salesChannelContext->getContext()
            ) . \PHP_EOL;

            return $this->replaceSeoUrlPlaceholder($content, $productExport, $salesChannelContext);
        } catch (StringTemplateRenderingException $exception) {
            $renderProductException = ProductExportException::renderProductException($exception->getMessage());
            $this->logException($salesChannelContext->getContext(), $renderProductException);

            throw $renderProductException;
        }
    }

    private function logException(
        Context $context,
        \Exception $exception
    ): void {
        $loggingEvent = new ProductExportLoggingEvent(
            $context,
            $exception->getMessage(),
            Level::Warning,
            $exception
        );

        $this->eventDispatcher->dispatch($loggingEvent);
    }

    private function replaceSeoUrlPlaceholder(
        string $content,
        ProductExportEntity $productExportEntity,
        SalesChannelContext $salesChannelContext
    ): string {
        return $this->seoUrlPlaceholderHandler->replace(
            $content,
            $productExportEntity->getSalesChannelDomain()->getUrl(),
            $salesChannelContext
        );
    }
}
