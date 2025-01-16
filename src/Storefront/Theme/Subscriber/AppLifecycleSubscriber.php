<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Subscriber;

use Cicada\Core\Framework\App\Event\AppDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\ThemeLifecycleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('framework')]
class AppLifecycleSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeLifecycleService $themeLifecycleService,
        private readonly EntityRepository $appRepository
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AppDeletedEvent::class => 'onAppDeleted',
        ];
    }

    public function onAppDeleted(AppDeletedEvent $event): void
    {
        if ($event->keepUserData()) {
            return;
        }

        $app = $this->appRepository->search(new Criteria([$event->getAppId()]), $event->getContext())->first();

        if ($app === null) {
            return;
        }

        $this->themeLifecycleService->removeTheme($app->getName(), $event->getContext());
    }
}
