<?php declare(strict_types=1);

use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;

new OneToOneAssociationField('prop', 'storageName', 'referenceField', 'referenceClass', true);
new ManyToOneAssociationField('prop', 'storageName', 'referenceClass', 'referenceField', true);
