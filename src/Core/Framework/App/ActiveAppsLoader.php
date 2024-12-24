<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal only for use by the app-system
 *
 * @phpstan-type App array{name: string, path: string, author: string|null, selfManaged: bool}
 */
#[Package('core')]
class ActiveAppsLoader implements ResetInterface
{
    /**
     * @var array<App>|null
     */
    private ?array $activeApps = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly AppLoader $appLoader,
        private readonly string $projectDir
    ) {
    }

    /**
     * @return array<App>
     */
    public function getActiveApps(): array
    {
        // @deprecated tag:v6.7.0 - remove if condition
        if (EnvironmentHelper::getVariable('DISABLE_EXTENSIONS', false)) {
            return [];
        }

        if ($this->activeApps === null) {
            $this->activeApps = $this->loadApps();
        }

        return $this->activeApps;
    }

    public function reset(): void
    {
        $this->activeApps = null;
    }

    /**
     * @return array<App>
     */
    private function loadApps(): array
    {
        try {
            $data = $this->connection->fetchAllAssociative('
                SELECT `name`, `path`, `author`, `self_managed`
                FROM `app`
                WHERE `active` = 1
            ');

            return array_map(fn (array $app) => [
                'name' => $app['name'],
                'path' => $app['path'],
                'author' => $app['author'],
                'selfManaged' => (bool) $app['self_managed'],
            ], $data);
        } catch (\Throwable $e) {
            if (\defined('\STDERR')) {
                fwrite(\STDERR, 'Warning: Failed to load apps. Loading apps from local. Message: ' . $e->getMessage() . \PHP_EOL);
            }

            return array_map(fn (Manifest $manifest) => [
                'name' => $manifest->getMetadata()->getName(),
                'path' => Path::makeRelative($manifest->getPath(), $this->projectDir),
                'author' => $manifest->getMetadata()->getAuthor(),
                'selfManaged' => false,
            ], $this->appLoader->load());
        }
    }
}
