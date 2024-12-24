<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
#[Package('core')]
class CurrencyValidator implements EventSubscriberInterface
{
    final public const VIOLATION_DELETE_DEFAULT_CURRENCY = 'delete_default_currency_violation';

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    public function preValidate(PreWriteValidationEvent $event): void
    {
        $commands = $event->getCommands();
        $violations = new ConstraintViolationList();

        foreach ($commands as $command) {
            if (!($command instanceof DeleteCommand) || $command->getEntityName() !== CurrencyDefinition::ENTITY_NAME) {
                continue;
            }

            $pk = $command->getPrimaryKey();
            $id = mb_strtolower((string) Uuid::fromBytesToHex($pk['id']));
            if ($id !== Defaults::CURRENCY) {
                continue;
            }

            $msgTpl = 'The default currency {{ id }} cannot be deleted.';
            $parameters = ['{{ id }}' => $id];
            $msg = \sprintf('The default currency %s cannot be deleted.', $id);
            $violation = new ConstraintViolation(
                $msg,
                $msgTpl,
                $parameters,
                null,
                '/' . $id,
                $id,
                null,
                self::VIOLATION_DELETE_DEFAULT_CURRENCY
            );

            $violations->add($violation);
        }

        if ($violations->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violations));
        }
    }
}
