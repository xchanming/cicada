<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Update;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException;
use Cicada\Core\Framework\Store\Services\AbstractExtensionDataProvider;
use Cicada\Core\Framework\Store\Services\AbstractStoreAppLifecycleService;
use Cicada\Core\Framework\Store\Services\ExtensionDownloader;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;

/**
 * @internal
 */
#[Package('core')]
class AppUpdater extends AbstractAppUpdater
{
    /**
     * @param EntityRepository<AppCollection> $appRepo
     */
    public function __construct(
        private readonly AbstractExtensionDataProvider $extensionDataProvider,
        private readonly EntityRepository $appRepo,
        private readonly ExtensionDownloader $downloader,
        private readonly AbstractStoreAppLifecycleService $appLifecycle
    ) {
    }

    public function updateApps(Context $context): void
    {
        $extensions = $this->extensionDataProvider->getInstalledExtensions($context, true);
        $extensions = $extensions->filterByType(ExtensionStruct::EXTENSION_TYPE_APP);

        $outdatedApps = [];

        foreach ($extensions as $extension) {
            $id = $extension->getLocalId();
            if (!$id) {
                continue;
            }
            $localApp = $this->appRepo->search(new Criteria([$id]), $context)->getEntities()->first();
            if ($localApp === null) {
                continue;
            }

            $nextVersion = $extension->getLatestVersion();
            if (!$nextVersion) {
                continue;
            }

            if (version_compare($nextVersion, $localApp->getVersion()) > 0) {
                $outdatedApps[] = $extension;
            }
        }
        foreach ($outdatedApps as $app) {
            $this->downloader->download($app->getName(), $context);

            try {
                $this->appLifecycle->updateExtension($app->getName(), false, $context);
            } catch (ExtensionUpdateRequiresConsentAffirmationException) {
                // Ignore updates that require consent
            }
        }
    }

    protected function getDecorated(): AbstractAppUpdater
    {
        throw new DecorationPatternException(self::class);
    }
}
