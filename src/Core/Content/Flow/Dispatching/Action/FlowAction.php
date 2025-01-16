<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
abstract class FlowAction
{
    /**
     * @return array<int, string>
     */
    abstract public function requirements(): array;

    abstract public function handleFlow(StorableFlow $flow): void;

    abstract public static function getName(): string;
}
