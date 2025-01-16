<?php
declare(strict_types=1);

namespace Cicada\Storefront\Theme\ConfigLoader;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\Event\ThemeAssignedEvent;
use Cicada\Storefront\Theme\Event\ThemeConfigChangedEvent;
use Cicada\Storefront\Theme\Event\ThemeConfigResetEvent;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('framework')]
class StaticFileConfigDumper implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractConfigLoader $configLoader,
        private readonly AbstractAvailableThemeProvider $availableThemeProvider,
        private readonly FilesystemOperator $privateFilesystem,
        private readonly FilesystemOperator $temporaryFilesystem
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ThemeConfigChangedEvent::class => 'dumpConfigFromEvent',
            ThemeAssignedEvent::class => 'dumpConfigFromEvent',
            ThemeConfigResetEvent::class => 'dumpConfigFromEvent',
        ];
    }

    /**
     * @param array<string, FileCollection|string> $dump
     */
    public function dumpConfigInVar(string $filePath, array $dump): void
    {
        $this->temporaryFilesystem->write($filePath, \json_encode($dump, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    }

    public function dumpConfig(Context $context): void
    {
        $salesChannelToTheme = $this->availableThemeProvider->load($context, false);
        $this->privateFilesystem->write(StaticFileAvailableThemeProvider::THEME_INDEX, \json_encode($salesChannelToTheme, \JSON_THROW_ON_ERROR));

        foreach ($salesChannelToTheme as $themeId) {
            $struct = $this->configLoader->load($themeId, $context);

            $path = \sprintf('theme-config/%s.json', $themeId);

            $this->privateFilesystem->write($path, \json_encode($struct->jsonSerialize(), \JSON_THROW_ON_ERROR));
        }
    }

    public function dumpConfigFromEvent(): void
    {
        $this->dumpConfig(Context::createDefaultContext());
    }
}
