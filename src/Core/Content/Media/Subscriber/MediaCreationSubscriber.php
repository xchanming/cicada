<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Subscriber;

use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWriteEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
class MediaCreationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWriteEvent::class => 'beforeWrite',
        ];
    }

    public function beforeWrite(EntityWriteEvent $event): void
    {
        $this->filterFilePath($this->getAffected(MediaThumbnailDefinition::ENTITY_NAME, $event));
        $this->filterFilePath($this->getAffected(MediaFolderDefinition::ENTITY_NAME, $event));
        $this->filterFilePath($this->getAffected(MediaDefinition::ENTITY_NAME, $event));
    }

    /**
     * @param array<WriteCommand> $commands
     */
    private function filterFilePath(array $commands): void
    {
        foreach ($commands as $command) {
            $path = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $command->getPayload()['path']);

            $command->addPayload('path', \is_string($path) ? $path : null);
        }
    }

    /**
     * @return array<WriteCommand>
     */
    private function getAffected(string $entityName, EntityWriteEvent $event): array
    {
        return array_filter($event->getCommandsForEntity($entityName), static function (WriteCommand $command) {
            if ($command instanceof DeleteCommand) {
                return false;
            }

            if ($command->hasField('path') && $command->getPayload()['path'] !== null) {
                return true;
            }

            return false;
        });
    }
}
