<?php declare(strict_types=1);

namespace Cicada\Administration\Notification\Extension;

use Cicada\Administration\Notification\NotificationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Integration\IntegrationDefinition;

#[Package('administration')]
class IntegrationExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('createdNotifications', NotificationDefinition::class, 'created_by_integration_id', 'id')
        );
    }

    public function getDefinitionClass(): string
    {
        return IntegrationDefinition::class;
    }

    public function getEntityName(): string
    {
        return IntegrationDefinition::ENTITY_NAME;
    }
}
