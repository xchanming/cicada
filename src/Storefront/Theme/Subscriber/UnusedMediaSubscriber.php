<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Subscriber;

use Cicada\Core\Content\Media\Event\UnusedMediaSearchEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Theme\ThemeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('storefront')]
class UnusedMediaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $themeRepository,
        private readonly ThemeService $themeService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UnusedMediaSearchEvent::class => 'removeUsedMedia',
        ];
    }

    public function removeUsedMedia(UnusedMediaSearchEvent $event): void
    {
        $context = Context::createDefaultContext();
        /** @var array<string> $allThemeIds */
        $allThemeIds = $this->themeRepository->searchIds(new Criteria(), $context)->getIds();

        $mediaIds = [];
        foreach ($allThemeIds as $themeId) {
            $config = $this->themeService->getThemeConfiguration($themeId, false, $context);

            foreach ($config['fields'] ?? [] as $data) {
                if ($data['type'] === 'media' && $data['value'] && Uuid::isValid($data['value'])) {
                    $mediaIds[] = $data['value'];
                }
            }
        }

        $event->markAsUsed(array_unique($mediaIds));
    }
}
