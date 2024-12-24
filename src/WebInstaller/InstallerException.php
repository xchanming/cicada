<?php declare(strict_types=1);

namespace Cicada\WebInstaller;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class InstallerException extends \RuntimeException
{
    public static function cannotFindCicadaInstallation(): self
    {
        return new self('Could not find Cicada installation');
    }

    public static function cannotFindComposerLock(): self
    {
        return new self('Could not find composer.lock file');
    }

    public static function cannotFindCicadaInComposerLock(): self
    {
        return new self('Could not find Cicada in composer.lock file');
    }
}
