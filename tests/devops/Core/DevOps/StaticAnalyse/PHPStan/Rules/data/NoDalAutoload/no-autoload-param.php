<?php declare(strict_types=1);

namespace Cicada\Core\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;

/**
 * @internal
 */
class Definition
{
    public const ENTITY_NAME = 'my-entity';

    public function __construct()
    {
        new OneToOneAssociationField('prop', 'storageName', 'referenceField', 'referenceClass');
        new ManyToOneAssociationField('prop', 'storageName', 'referenceClass', 'referenceField');
    }
}
