<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme\fixtures;

use Cicada\Storefront\Event\ThemeCompilerConcatenatedScriptsEvent;
use Cicada\Storefront\Event\ThemeCompilerConcatenatedStylesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class MockThemeCompilerConcatenatedSubscriber implements EventSubscriberInterface
{
    final public const STYLES_CONCAT = '.mock-selector {}';
    final public const SCRIPTS_CONCAT = 'console.log(\'bar\');';

    public static function getSubscribedEvents(): array
    {
        return [
            ThemeCompilerConcatenatedStylesEvent::class => 'onGetConcatenatedStyles',
            ThemeCompilerConcatenatedScriptsEvent::class => 'onGetConcatenatedScripts',
        ];
    }

    public function onGetConcatenatedStyles(ThemeCompilerConcatenatedStylesEvent $event): void
    {
        $event->setConcatenatedStyles($event->getConcatenatedStyles() . self::STYLES_CONCAT);
    }

    public function onGetConcatenatedScripts(ThemeCompilerConcatenatedScriptsEvent $event): void
    {
        $event->setConcatenatedScripts($event->getConcatenatedScripts() . self::SCRIPTS_CONCAT);
    }
}
