<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Staging\Handler;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
#[Package('core')]
readonly class StagingAppHandler
{
    public function __construct(
        private Connection $connection,
        private SystemConfigService $systemConfigService
    ) {
    }

    public function __invoke(SetupStagingEvent $event): void
    {
        $this->deleteAppsWithAppServer($event);

        $this->systemConfigService->delete(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY);
    }

    private function deleteAppsWithAppServer(SetupStagingEvent $event): void
    {
        $apps = $this->connection->fetchAllAssociative('SELECT id, integration_id, name FROM app WHERE app_secret IS NOT NULL');

        foreach ($apps as $app) {
            $this->connection->delete('app', ['id' => $app['id']]);
            $this->connection->delete('integration', ['id' => $app['integration_id']]);

            $event->io->info(\sprintf('Uninstalled app %s, install app again to establish a correct connection ', $app['name']));
        }
    }
}
