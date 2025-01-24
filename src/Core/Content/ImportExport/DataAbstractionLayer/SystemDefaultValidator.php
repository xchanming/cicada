<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer;

use Cicada\Core\Content\ImportExport\Exception\DeleteDefaultProfileException;
use Cicada\Core\Content\ImportExport\ImportExportProfileDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class SystemDefaultValidator implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    /**
     * @internal
     *
     * @throws DeleteDefaultProfileException
     */
    public function preValidate(PreWriteValidationEvent $event): void
    {
        $ids = [];
        $writeCommands = $event->getCommands();

        foreach ($writeCommands as $command) {
            if ($command->getEntityName() === ImportExportProfileDefinition::ENTITY_NAME
                && $command instanceof DeleteCommand
            ) {
                $ids[] = $command->getPrimaryKey()['id'];
            }
        }

        $filteredIds = $this->filterSystemDefaults($ids);
        if (!empty($filteredIds)) {
            $event->getExceptions()->add(new DeleteDefaultProfileException($filteredIds));
        }
    }

    /**
     * @param string[] $ids
     *
     * @return string[]
     */
    private function filterSystemDefaults(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $result = $this->connection->executeQuery(
            'SELECT id FROM import_export_profile WHERE id IN (:idList) AND system_default = 1',
            ['idList' => $ids],
            ['idList' => ArrayParameterType::BINARY]
        );

        return $result->fetchFirstColumn();
    }
}
