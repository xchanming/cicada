<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class TreeBreadcrumbField extends JsonField
{
    public function __construct(
        string $storageName = 'breadcrumb',
        string $propertyName = 'breadcrumb',
        private readonly string $nameField = 'name'
    ) {
        parent::__construct($storageName, $propertyName);

        $this->addFlags(new WriteProtected(Context::SYSTEM_SCOPE));
    }

    public function getNameField(): string
    {
        return $this->nameField;
    }
}
