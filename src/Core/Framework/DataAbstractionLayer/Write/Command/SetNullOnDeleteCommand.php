<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Write\Command;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class SetNullOnDeleteCommand extends UpdateCommand
{
    /**
     * @deprecated tag:v6.7.0 - Property will be removed
     */
    protected EntityDefinition $definition;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $primaryKey
     */
    public function __construct(
        EntityDefinition $definition,
        array $payload,
        array $primaryKey,
        EntityExistence $existence,
        string $path,
        private readonly bool $enforcedByConstraint
    ) {
        $this->definition = $definition;

        parent::__construct($definition, $payload, $primaryKey, $existence, $path);
    }

    public function isValid(): bool
    {
        // prevent execution if the constraint is enforced on DB level
        return !$this->enforcedByConstraint;
    }

    public function getPrivilege(): ?string
    {
        return null;
    }

    /**
     * @deprecated tag:v6.7.0 - Method will be removed
     */
    public function getDefinition(): EntityDefinition
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, 'getDefinition', 'v6.7.0.0')
        );

        return $this->definition;
    }
}
