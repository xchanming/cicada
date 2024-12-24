<?php
declare(strict_types=1);

namespace Cicada\Storefront\Theme\ConfigLoader;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('storefront')]
class StaticFileAvailableThemeProvider extends AbstractAvailableThemeProvider
{
    final public const THEME_INDEX = 'theme-config/index.json';

    /**
     * @internal
     */
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    public function getDecorated(): AbstractAvailableThemeProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(Context $context, bool $activeOnly): array
    {
        if (!$this->filesystem->fileExists(self::THEME_INDEX)) {
            throw new \RuntimeException('Cannot find theme configuration. Did you run bin/console theme:dump');
        }

        return json_decode((string) $this->filesystem->read(self::THEME_INDEX), true, 512, \JSON_THROW_ON_ERROR);
    }
}
