<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class TreePathField extends LongTextField
{
    public function __construct(
        string $storageName,
        string $propertyName,
        private readonly string $pathField = 'id'
    ) {
        parent::__construct($storageName, $propertyName);

        $this->addFlags(new WriteProtected(Context::SYSTEM_SCOPE));
    }

    public function getPathField(): string
    {
        return $this->pathField;
    }
}
