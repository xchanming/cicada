<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Subscriber;

use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('discovery')]
class MediaVisibilityRestrictionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EntitySearchedEvent::class => 'securePrivateFolders',
        ];
    }

    public function securePrivateFolders(EntitySearchedEvent $event): void
    {
        if ($event->getContext()->getScope() === Context::SYSTEM_SCOPE) {
            return;
        }

        if ($event->getDefinition()->getEntityName() === MediaFolderDefinition::ENTITY_NAME) {
            $event->getCriteria()->addFilter(
                new MultiFilter('OR', [
                    new EqualsFilter('media_folder.configuration.private', false),
                    new EqualsFilter('media_folder.configuration.private', null),
                ])
            );

            return;
        }

        if ($event->getDefinition()->getEntityName() === MediaDefinition::ENTITY_NAME) {
            $event->getCriteria()->addFilter(
                new MultiFilter('OR', [
                    new EqualsFilter('private', false),
                    new MultiFilter('AND', [
                        new EqualsFilter('private', true),
                        new EqualsFilter('mediaFolder.defaultFolder.entity', 'product_download'),
                    ]),
                ])
            );
        }
    }
}
