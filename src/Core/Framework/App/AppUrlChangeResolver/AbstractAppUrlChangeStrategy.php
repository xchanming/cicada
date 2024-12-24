<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\AppUrlChangeResolver;

use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Lifecycle\Registration\AppRegistrationService;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class AbstractAppUrlChangeStrategy
{
    /**
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly AppLoader $appLoader,
        private readonly EntityRepository $appRepository,
        private readonly AppRegistrationService $registrationService
    ) {
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function resolve(Context $context): void;

    abstract public function getDecorated(): self;

    /**
     * @param callable(Manifest, AppEntity, Context): void $callback
     */
    protected function forEachInstalledApp(Context $context, callable $callback): void
    {
        $manifests = $this->appLoader->load();
        $apps = $this->appRepository->search(new Criteria(), $context)->getEntities();

        foreach ($manifests as $manifest) {
            $app = $this->getAppForManifest($manifest, $apps);

            if (!$app || !$manifest->getSetup()) {
                continue;
            }

            $callback($manifest, $app, $context);
        }
    }

    protected function reRegisterApp(Manifest $manifest, AppEntity $app, Context $context): void
    {
        $secret = AccessKeyHelper::generateSecretAccessKey();

        $this->appRepository->update([
            [
                'id' => $app->getId(),
                'integration' => [
                    'id' => $app->getIntegrationId(),
                    'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
                    'secretAccessKey' => $secret,
                ],
            ],
        ], $context);

        $this->registrationService->registerApp($manifest, $app->getId(), $secret, $context);
    }

    private function getAppForManifest(Manifest $manifest, AppCollection $installedApps): ?AppEntity
    {
        $matchedApps = $installedApps->filter(static fn (AppEntity $installedApp): bool => $installedApp->getName() === $manifest->getMetadata()->getName());

        return $matchedApps->first();
    }
}
