<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Protection
{
    final public const SYSTEM_SCOPE = Context::SYSTEM_SCOPE;
    final public const USER_SCOPE = Context::USER_SCOPE;
    final public const CRUD_API_SCOPE = Context::CRUD_API_SCOPE;

    /**
     * @param array<string> $write
     */
    public function __construct(public array $write)
    {
    }
}
