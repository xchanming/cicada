<?php declare(strict_types=1);

namespace Cicada\Core\Service;

use Cicada\Core\Framework\App\Source\TemporaryDirectoryFactory as CoreTemporaryDirectoryFactory;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
#[Package('core')]
class TemporaryDirectoryFactory extends CoreTemporaryDirectoryFactory
{
    public function __construct(private string $projectDirectory)
    {
    }

    public function path(): string
    {
        return Path::join($this->projectDirectory, 'var/services');
    }
}
