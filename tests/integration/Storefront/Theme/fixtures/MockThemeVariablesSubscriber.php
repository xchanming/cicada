<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme\fixtures;

use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class MockThemeVariablesSubscriber implements EventSubscriberInterface
{
    protected SystemConfigService $systemConfig;

    public function __construct(SystemConfigService $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ThemeCompilerEnrichScssVariablesEvent::class => 'onAddVariables',
        ];
    }

    public function onAddVariables(ThemeCompilerEnrichScssVariablesEvent $event): void
    {
        $event->addVariable('mock-variable-black', '#000000');
        $event->addVariable('mock-variable-special', 'Special value with quotes', true);
    }
}
