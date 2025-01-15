<?php declare(strict_types=1);

namespace Cicada\Administration\Notification;

use Cicada\Core\Framework\DataAbstractionLayer\BulkEntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Integration\IntegrationDefinition;
use Cicada\Core\System\User\UserDefinition;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('administration')]
class NotificationBulkEntityExtension extends BulkEntityExtension
{
    public function collect(): \Generator
    {
        yield IntegrationDefinition::ENTITY_NAME => [
            new OneToManyAssociationField('createdNotifications', NotificationDefinition::class, 'created_by_integration_id', 'id'),
        ];

        yield UserDefinition::ENTITY_NAME => [
            new OneToManyAssociationField('createdNotifications', NotificationDefinition::class, 'created_by_user_id', 'id'),
        ];
    }
}
