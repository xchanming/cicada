<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching;

use Cicada\Core\Framework\Log\Package;

/**
 * When a flow action implements this interface, it will be executed within a database transaction.
 */
#[Package('services-settings')]
interface TransactionalAction
{
}
