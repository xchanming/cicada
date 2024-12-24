<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginManagementService;
use Cicada\Core\Framework\Store\Exception\StoreApiException;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\Framework\Store\Struct\PluginDownloadDataStruct;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[Package('checkout')]
class ExtensionDownloader
{
    private readonly string $relativePluginDir;

    public function __construct(
        private readonly EntityRepository $pluginRepository,
        private readonly StoreClient $storeClient,
        private readonly PluginManagementService $pluginManagementService,
        string $pluginDir,
        string $projectDir
    ) {
        $this->relativePluginDir = (new Filesystem())->makePathRelative($pluginDir, $projectDir);
    }

    public function download(string $technicalName, Context $context): PluginDownloadDataStruct
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('plugin.name', $technicalName));

        /** @var PluginEntity|null $plugin */
        $plugin = $this->pluginRepository->search($criteria, $context)->first();

        if ($plugin !== null && $plugin->getManagedByComposer() && !str_starts_with($plugin->getPath() ?? '', $this->relativePluginDir)) {
            throw StoreException::cannotDeleteManaged($plugin->getName());
        }

        try {
            $data = $this->storeClient->getDownloadDataForPlugin($technicalName, $context);
        } catch (ClientException $e) {
            throw new StoreApiException($e);
        }

        $this->pluginManagementService->downloadStorePlugin($data, $context);

        return $data;
    }
}
