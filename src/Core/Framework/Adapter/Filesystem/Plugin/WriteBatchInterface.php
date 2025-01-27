<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Filesystem\Plugin;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
interface WriteBatchInterface
{
    public function writeBatch(CopyBatchInput ...$files): void;
}
