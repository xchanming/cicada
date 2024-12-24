<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\InAppPurchases\Response;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\AssignArrayTrait;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class InAppPurchasesResponse
{
    use AssignArrayTrait;

    /**
     * @var list<string>
     */
    public array $purchases = [];
}
