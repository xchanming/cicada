<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Command;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\PluginNotFoundException;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Core\Framework\Plugin\PluginManagementService;
use Cicada\Core\Framework\Store\Exception\StoreApiException;
use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\System\User\UserCollection;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[AsCommand(
    name: 'store:download',
    description: 'Downloads a plugin from the store',
)]
#[Package('checkout')]
class StoreDownloadCommand extends Command
{
    private readonly string $relativePluginDir;

    /**
     * @param EntityRepository<PluginCollection> $pluginRepo
     * @param EntityRepository<UserCollection> $userRepository
     */
    public function __construct(
        private readonly StoreClient $storeClient,
        private readonly EntityRepository $pluginRepo,
        private readonly PluginManagementService $pluginManagementService,
        private readonly PluginLifecycleService $pluginLifecycleService,
        private readonly EntityRepository $userRepository,
        string $pluginDir,
        string $projectDir,
    ) {
        parent::__construct();

        $this->relativePluginDir = (new Filesystem())->makePathRelative($pluginDir, $projectDir);
    }

    protected function configure(): void
    {
        $this->addOption('pluginName', 'p', InputOption::VALUE_REQUIRED, 'Name of plugin')
            ->addOption('language', 'l', InputOption::VALUE_OPTIONAL, 'Language')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'User')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createCLIContext();

        $pluginName = (string) $input->getOption('pluginName');
        $user = $input->getOption('user');

        $context = $this->getUserContextFromInput($user, $context);

        $this->validatePluginIsNotManagedByComposer($pluginName, $context);

        try {
            $data = $this->storeClient->getDownloadDataForPlugin($pluginName, $context);
        } catch (ClientException $exception) {
            throw new StoreApiException($exception);
        }

        $this->pluginManagementService->downloadStorePlugin($data, $context);

        try {
            $plugin = $this->getPluginFromInput($pluginName, $context);

            if ($plugin->getUpgradeVersion()) {
                $this->pluginLifecycleService->updatePlugin($plugin, $context);
            }
        } catch (PluginNotFoundException) {
            // don't update plugins that are not installed
        }

        return self::SUCCESS;
    }

    private function getUserContextFromInput(?string $userName, Context $context): Context
    {
        if (!$userName) {
            return $context;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('user.username', $userName));

        $userEntity = $this->userRepository->search($criteria, $context)->getEntities()->first();
        if ($userEntity === null) {
            return $context;
        }

        return Context::createCLIContext(new AdminApiSource($userEntity->getId()));
    }

    private function validatePluginIsNotManagedByComposer(string $pluginName, Context $context): void
    {
        try {
            $plugin = $this->getPluginFromInput($pluginName, $context);
        } catch (PluginNotFoundException) {
            // plugins no installed can still be downloaded
            return;
        }

        if ($plugin->getManagedByComposer() && !str_starts_with($plugin->getPath() ?? '', $this->relativePluginDir)) {
            throw StoreException::cannotDeleteManaged($pluginName);
        }
    }

    private function getPluginFromInput(string $pluginName, Context $context): PluginEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('plugin.name', $pluginName));

        $plugin = $this->pluginRepo->search($criteria, $context)->getEntities()->first();
        if ($plugin === null) {
            throw new PluginNotFoundException($pluginName);
        }

        return $plugin;
    }
}
