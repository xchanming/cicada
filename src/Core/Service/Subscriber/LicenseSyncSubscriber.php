<?php declare(strict_types=1);

namespace Cicada\Core\Service\Subscriber;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Event\AppInstalledEvent;
use Cicada\Core\Framework\App\Event\AppUpdatedEvent;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Service\ServiceClientFactory;
use Cicada\Core\Service\ServiceException;
use Cicada\Core\Service\ServiceRegistryClient;
use Cicada\Core\Service\ServiceRegistryEntry;
use Cicada\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class LicenseSyncSubscriber implements EventSubscriberInterface
{
    public const CONFIG_STORE_LICENSE_KEY = 'core.store.licenseKey';

    public const CONFIG_STORE_LICENSE_HOST = 'core.store.licenseHost';

    public function __construct(
        private readonly SystemConfigService $config,
        private readonly ServiceRegistryClient $serviceRegistryClient,
        private readonly EntityRepository $appRepository,
        private readonly LoggerInterface $logger,
        private readonly ServiceClientFactory $clientFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AppInstalledEvent::class => 'serviceInstalled',
            AppUpdatedEvent::class => 'serviceInstalled',
            SystemConfigChangedEvent::class => 'syncLicense',
        ];
    }

    public function syncLicense(SystemConfigChangedEvent $event): void
    {
        $key = $event->getKey();
        $value = $event->getValue();

        if (!\in_array($key, [self::CONFIG_STORE_LICENSE_KEY, self::CONFIG_STORE_LICENSE_HOST], true) || !\is_string($value)) {
            return;
        }

        $context = Context::createDefaultContext();

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('selfManaged', true));

        $apps = $this->appRepository->search($criteria, $context)->getEntities();

        $licenseKey = $key === self::CONFIG_STORE_LICENSE_KEY ? $value : null;
        $licenseHost = $key === self::CONFIG_STORE_LICENSE_HOST ? $value : null;

        /** @var AppEntity $app */
        foreach ($apps as $app) {
            if (!$app->getAppSecret() || !$app->isSelfManaged()) {
                continue;
            }

            $serviceEntry = $this->serviceRegistryClient->get($app->getName());

            $this->syncLicenseByService($serviceEntry, $app, $context, $licenseKey, $licenseHost);
        }
    }

    public function serviceInstalled(AppInstalledEvent|AppUpdatedEvent $event): void
    {
        $app = $event->getApp();
        $context = $event->getContext();
        $source = $context->getSource();

        if (!$app->getAppSecret() || !$app->isSelfManaged()) {
            return;
        }

        if ($source instanceof AdminApiSource && $app->getIntegrationId() !== $source->getIntegrationId()) {
            return;
        }

        try {
            $serviceEntry = $this->serviceRegistryClient->get($app->getName());
            $this->syncLicenseByService($serviceEntry, $app, $context);
        } catch (\Throwable $e) {
            $this->logger->warning('Could not sync license', ['exception' => $e->getMessage()]);
        }
    }

    private function syncLicenseByService(ServiceRegistryEntry $serviceEntry, AppEntity $app, Context $context, ?string $licenseKey = null, ?string $licenseHost = null): void
    {
        if ($serviceEntry->licenseSyncEndPoint === null) {
            return;
        }

        if ($licenseKey === null) {
            $licenseKey = $this->config->getString(self::CONFIG_STORE_LICENSE_KEY);
        }

        if ($licenseHost === null) {
            $licenseHost = $this->config->getString(self::CONFIG_STORE_LICENSE_HOST);
        }

        try {
            $client = $this->clientFactory->newAuthenticatedFor($serviceEntry, $app, $context);

            $client->syncLicense($licenseKey, $licenseHost);
        } catch (ServiceException|AppUrlChangeDetectedException $e) {
            $this->logger->warning('Could not sync license', ['exception' => $e->getMessage()]);
        }
    }
}
