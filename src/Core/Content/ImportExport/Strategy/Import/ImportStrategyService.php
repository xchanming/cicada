<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Strategy\Import;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\ImportExport\Struct\ImportResult;
use Cicada\Core\Content\ImportExport\Struct\Progress;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
interface ImportStrategyService
{
    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $row
     */
    public function import(
        array $record,
        array $row,
        Config $config,
        Progress $progress,
        Context $context,
    ): ImportResult;

    public function commit(Config $config, Progress $progress, Context $context): ImportResult;
}
