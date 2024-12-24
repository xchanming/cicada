<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\EntityProtection\_fixtures;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\EntityProtectionCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;
use Cicada\Core\System\SystemConfig\SystemConfigDefinition;

/**
 * @internal
 */
class SystemConfigExtension extends EntityExtension
{
    /**
     * {@inheritdoc}
     */
    public function getDefinitionClass(): string
    {
        return SystemConfigDefinition::class;
    }

    public function extendProtections(EntityProtectionCollection $protections): void
    {
        $protections->add(new WriteProtection(Context::SYSTEM_SCOPE, Context::USER_SCOPE));
    }

    public function getEntityName(): string
    {
        return SystemConfigDefinition::ENTITY_NAME;
    }
}
