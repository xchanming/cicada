<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Twig;

use Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy\TemplateNamespaceHierarchyBuilderInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\SalesChannelRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('framework')]
class ThemeNamespaceHierarchyBuilder implements TemplateNamespaceHierarchyBuilderInterface, EventSubscriberInterface, ResetInterface
{
    /**
     * @var array<int|string, bool>
     */
    private array $themes = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeInheritanceBuilderInterface $themeInheritanceBuilder,
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'requestEvent',
            KernelEvents::EXCEPTION => 'requestEvent',
        ];
    }

    public function requestEvent(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $this->themes = $this->detectedThemes($request);
    }

    public function buildNamespaceHierarchy(array $namespaceHierarchy): array
    {
        if (empty($this->themes)) {
            return $namespaceHierarchy;
        }

        return $this->themeInheritanceBuilder->build($namespaceHierarchy, $this->themes);
    }

    public function reset(): void
    {
        $this->themes = [];
    }

    /**
     * @return array<int|string, bool>
     */
    private function detectedThemes(Request $request): array
    {
        $themes = [];
        // get name if theme is not inherited
        $theme = $request->attributes->get(SalesChannelRequest::ATTRIBUTE_THEME_NAME);

        if (!$theme) {
            // get theme name from base theme because for inherited themes the name is always null
            $theme = $request->attributes->get(SalesChannelRequest::ATTRIBUTE_THEME_BASE_NAME);
        }

        if (!$theme) {
            return [];
        }

        $themes[$theme] = true;
        $themes['Storefront'] = true;

        return $themes;
    }
}
