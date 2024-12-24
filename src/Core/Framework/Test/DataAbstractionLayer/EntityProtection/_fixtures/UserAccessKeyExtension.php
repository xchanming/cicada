<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\EntityProtection\_fixtures;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\EntityProtectionCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\ReadProtection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;
use Cicada\Core\System\User\Aggregate\UserAccessKey\UserAccessKeyDefinition;

/**
 * @internal
 */
class UserAccessKeyExtension extends EntityExtension
{
    /**
     * {@inheritdoc}
     */
    public function getDefinitionClass(): string
    {
        return UserAccessKeyDefinition::class;
    }

    public function extendProtections(EntityProtectionCollection $protections): void
    {
        $protections->add(new ReadProtection(Context::SYSTEM_SCOPE, Context::USER_SCOPE));
        $protections->add(new WriteProtection(Context::SYSTEM_SCOPE, Context::USER_SCOPE));
    }

    public function getEntityName(): string
    {
        return UserAccessKeyDefinition::ENTITY_NAME;
    }
}
