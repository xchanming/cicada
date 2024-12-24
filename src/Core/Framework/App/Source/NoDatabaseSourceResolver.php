<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Source;

use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Filesystem;

/**
 * @internal
 *
 * @phpstan-import-type App from ActiveAppsLoader
 */
#[Package('core')]
class NoDatabaseSourceResolver
{
    /**
     * @var array<string, App>
     */
    private array $activeApps = [];

    public function __construct(ActiveAppsLoader $activeAppsLoader)
    {
        $activeApps = $activeAppsLoader->getActiveApps();
        $this->activeApps = array_combine(
            array_map(fn (array $app) => $app['name'], $activeApps),
            $activeApps
        );
    }

    public function filesystem(string $appName): Filesystem
    {
        if (!isset($this->activeApps[$appName])) {
            throw AppException::notFoundByField($appName, 'name');
        }

        return new Filesystem($this->activeApps[$appName]['path']);
    }
}
