<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
abstract class AbstractFieldResolver
{
    abstract public function join(FieldResolverContext $context): string;
}
