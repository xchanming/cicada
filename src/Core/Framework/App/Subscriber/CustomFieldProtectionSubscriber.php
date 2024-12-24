<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Subscriber;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class CustomFieldProtectionSubscriber implements EventSubscriberInterface
{
    final public const VIOLATION_NO_PERMISSION = 'no_permission_violation';

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'checkWrite',
        ];
    }

    public function checkWrite(PreWriteValidationEvent $event): void
    {
        $context = $event->getContext();

        if ($context->getSource() instanceof SystemSource || $context->getScope() === Context::SYSTEM_SCOPE) {
            return;
        }

        $integrationId = $this->getIntegrationId($context);
        $violationList = new ConstraintViolationList();

        foreach ($event->getCommands() as $command) {
            if ($command->getEntityName() !== CustomFieldSetDefinition::ENTITY_NAME || $command instanceof InsertCommand) {
                continue;
            }

            $appIntegrationId = $this->fetchIntegrationIdOfAssociatedApp($command);
            if (!$appIntegrationId) {
                continue;
            }

            if ($integrationId !== $appIntegrationId) {
                $this->addViolation($violationList, $command);
            }
        }
        if ($violationList->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violationList));
        }
    }

    private function getIntegrationId(Context $context): ?string
    {
        $source = $context->getSource();
        if (!($source instanceof AdminApiSource)) {
            return null;
        }

        return $source->getIntegrationId();
    }

    private function fetchIntegrationIdOfAssociatedApp(WriteCommand $command): ?string
    {
        $id = $command->getPrimaryKey()['id'];
        $integrationId = $this->connection->executeQuery('
            SELECT `app`.`integration_id`
            FROM `app`
            INNER JOIN `custom_field_set` ON `custom_field_set`.`app_id` = `app`.`id`
            WHERE `custom_field_set`.`id` = :customFieldSetId
        ', ['customFieldSetId' => $id])->fetchOne();

        if (!$integrationId) {
            return null;
        }

        return Uuid::fromBytesToHex($integrationId);
    }

    private function addViolation(ConstraintViolationList $violationList, WriteCommand $command): void
    {
        $violationList->add(
            $this->buildViolation(
                'No permissions to %privilege%".',
                ['%privilege%' => 'write:custom_field_set'],
                '/' . $command->getEntityName(),
                self::VIOLATION_NO_PERMISSION
            )
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private function buildViolation(
        string $messageTemplate,
        array $parameters,
        ?string $propertyPath = null,
        ?string $code = null
    ): ConstraintViolationInterface {
        return new ConstraintViolation(
            str_replace(array_keys($parameters), array_values($parameters), $messageTemplate),
            $messageTemplate,
            $parameters,
            null,
            $propertyPath,
            null,
            null,
            $code
        );
    }
}
