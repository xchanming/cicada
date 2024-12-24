<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ManyToManyIdField extends ListField
{
    public function __construct(
        string $storageName,
        string $propertyName,
        private readonly string $associationName
    ) {
        parent::__construct($storageName, $propertyName, IdField::class);
        $this->addFlags(new WriteProtected());
    }

    public function getAssociationName(): string
    {
        return $this->associationName;
    }
}
