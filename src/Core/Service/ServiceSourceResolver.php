<?php declare(strict_types=1);

namespace Cicada\Core\Service;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\AppExtractor;
use Cicada\Core\Framework\App\Exception\AppArchiveValidationFailure;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Source\Source;
use Cicada\Core\Framework\App\Source\TemporaryDirectoryFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginException;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Core\Service\Event\ServiceOutdatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem as Io;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 *
 * @phpstan-type ServiceSourceConfig array{version: string, hash: string, revision: string, zip-url: string}
 */
#[Package('core')]
class ServiceSourceResolver implements Source
{
    public function __construct(
        private readonly TemporaryDirectoryFactory $temporaryDirectoryFactory,
        private readonly ServiceClientFactory $serviceClientFactory,
        private readonly AppExtractor $appExtractor,
        private readonly Io $io,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function name(): string
    {
        return 'service';
    }

    public function filesystemForVersion(AppInfo $appInfo): Filesystem
    {
        return new Filesystem($this->downloadVersion(
            $this->serviceClientFactory->fromName($appInfo->name),
            $appInfo->name,
            $appInfo->zipUrl
        ));
    }

    public function supports(Manifest|AppEntity $app): bool
    {
        return match (true) {
            $app instanceof AppEntity => $app->getSourceType() === $this->name(),
            $app instanceof Manifest => preg_match('#^https?://#', $app->getPath()) && $app->getMetadata()->isSelfManaged(),
        };
    }

    public function filesystem(Manifest|AppEntity $app): Filesystem
    {
        $temporaryDirectory = $this->temporaryDirectoryFactory->path();

        $name = $app instanceof Manifest ? $app->getMetadata()->getName() : $app->getName();

        // app is already on the filesystem, use that
        if ($this->io->exists(Path::join($temporaryDirectory, $name))) {
            return new Filesystem(Path::join($temporaryDirectory, $name));
        }

        /** @var ServiceSourceConfig $sourceConfig */
        $sourceConfig = $app->getSourceConfig();

        return new Filesystem($this->checkVersionAndDownloadAppZip($name, $sourceConfig));
    }

    public function reset(array $filesystems): void
    {
    }

    /**
     * @param ServiceSourceConfig $sourceConfig
     */
    private function checkVersionAndDownloadAppZip(string $serviceName, array $sourceConfig): string
    {
        $client = $this->serviceClientFactory->fromName($serviceName);

        $latestAppInfo = $client->latestAppInfo();

        if (!$this->isLatestVersionInstalled($latestAppInfo, $sourceConfig)) {
            // the app revision has changed in the service, so we must update the app
            // this can happen if the system attempts to download the app, before a service update rollout has completed
            $this->eventDispatcher->dispatch(new ServiceOutdatedEvent($serviceName, Context::createDefaultContext()));

            // the update process will download and extract the app, so we can assume it's present on the FS now
            return Path::join($this->temporaryDirectoryFactory->path(), $serviceName);
        }

        return $this->downloadVersion($client, $serviceName, $sourceConfig['zip-url']);
    }

    private function downloadVersion(
        ServiceClient $client,
        string $serviceName,
        string $zipUrl,
    ): string {
        $destination = Path::join($this->temporaryDirectoryFactory->path(), $serviceName);
        $localZipLocation = Path::join($destination, $serviceName . '.zip');

        $this->io->mkdir($destination);

        try {
            $client->downloadAppZipForVersion($zipUrl, $localZipLocation);
        } catch (ServiceException $e) {
            throw AppException::cannotMountAppFilesystem($serviceName, $e); // @phpstan-ignore cicada.domainException
        }

        try {
            $this->appExtractor->extract(
                $localZipLocation,
                $this->temporaryDirectoryFactory->path(),
                $serviceName,
            );
        } catch (PluginException|AppArchiveValidationFailure $e) {
            throw AppException::cannotMountAppFilesystem($serviceName, $e); // @phpstan-ignore cicada.domainException
        } finally {
            $this->io->remove($localZipLocation);
        }

        return $destination;
    }

    /**
     * @param array{revision: string} $sourceConfig
     */
    private function isLatestVersionInstalled(AppInfo $latestAppInfo, array $sourceConfig): bool
    {
        return $latestAppInfo->revision === $sourceConfig['revision'];
    }
}
