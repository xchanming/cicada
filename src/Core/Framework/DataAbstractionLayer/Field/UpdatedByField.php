<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\UpdatedByFieldSerializer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\UserDefinition;

#[Package('core')]
class UpdatedByField extends FkField
{
    public function __construct(private readonly array $allowedWriteScopes = [Context::SYSTEM_SCOPE])
    {
        parent::__construct('updated_by_id', 'updatedById', UserDefinition::class);
    }

    public function getAllowedWriteScopes(): array
    {
        return $this->allowedWriteScopes;
    }

    protected function getSerializerClass(): string
    {
        return UpdatedByFieldSerializer::class;
    }
}
