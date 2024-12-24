<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Twig;

use Cicada\Core\Checkout\Document\DocumentGenerator\Counter;
use Cicada\Core\Checkout\Document\Event\DocumentTemplateRendererParameterEvent;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Adapter\Twig\TemplateFinder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Package('checkout')]
class DocumentTemplateRenderer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly TemplateFinder $templateFinder,
        private readonly Environment $twig,
        private readonly AbstractTranslator $translator,
        private readonly AbstractSalesChannelContextFactory $contextFactory,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(
        string $view,
        array $parameters = [],
        ?Context $context = null,
        ?string $salesChannelId = null,
        ?string $languageId = null,
        ?string $locale = null
    ): string {
        // If parameters for specific language setting provided, inject to translator
        if ($context !== null && $salesChannelId !== null && $languageId !== null && $locale !== null) {
            $this->translator->injectSettings(
                $salesChannelId,
                $languageId,
                $locale,
                $context
            );
            $salesChannelContext = $this->contextFactory->create(
                Uuid::randomHex(),
                $salesChannelId,
                [SalesChannelContextService::LANGUAGE_ID => $languageId]
            );

            $parameters['context'] = $salesChannelContext;
        }

        $documentTemplateRendererParameterEvent = new DocumentTemplateRendererParameterEvent($parameters);
        $this->eventDispatcher->dispatch($documentTemplateRendererParameterEvent);
        $parameters['extensions'] = $documentTemplateRendererParameterEvent->getExtensions();

        $parameters['counter'] = new Counter();

        $view = $this->resolveView($view);

        $rendered = $this->twig->render($view, $parameters);

        // If injected translator reject it
        if ($context !== null && $salesChannelId !== null && $languageId !== null && $locale !== null) {
            $this->translator->resetInjection();
        }

        return $rendered;
    }

    /**
     * @throws LoaderError
     */
    private function resolveView(string $view): string
    {
        $this->templateFinder->reset();

        return $this->templateFinder->find($view);
    }
}
