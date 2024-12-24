<?php declare(strict_types=1);

namespace Cicada\Core\Service\Subscriber;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Event\InstalledExtensionsListingLoadedEvent;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class InstalledExtensionsListingLoadedSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     *
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(private readonly EntityRepository $appRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InstalledExtensionsListingLoadedEvent::class => 'removeAppsWithService',
        ];
    }

    /**
     * Remove apps from the listing which have an installed service equivalent
     */
    public function removeAppsWithService(InstalledExtensionsListingLoadedEvent $event): void
    {
        $existingServices = $this->appRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('selfManaged', true)),
            $event->context
        )->getEntities();

        $names = array_values($existingServices->map(fn (AppEntity $app) => $app->getName()));

        $event->extensionCollection = $event->extensionCollection->filter(
            fn (ExtensionStruct $ext) => !\in_array($ext->getName(), $names, true)
        );
    }
}
