<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Subscriber;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeDefinition;
use Cicada\Core\System\SalesChannel\Exception\DefaultSalesChannelTypeCannotBeDeleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('discovery')]
class SalesChannelTypeValidator implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preWriteValidateEvent',
        ];
    }

    public function preWriteValidateEvent(PreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if (!$command instanceof DeleteCommand || $command->getEntityName() !== SalesChannelTypeDefinition::ENTITY_NAME) {
                continue;
            }

            $id = Uuid::fromBytesToHex($command->getPrimaryKey()['id']);

            if (\in_array($id, [Defaults::SALES_CHANNEL_TYPE_API, Defaults::SALES_CHANNEL_TYPE_STOREFRONT, Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON], true)) {
                $event->getExceptions()->add(new DefaultSalesChannelTypeCannotBeDeleted($id));
            }
        }
    }
}
