<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Struct;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
class ImportResult
{
    /**
     * @param EntityWrittenContainerEvent[] $results
     * @param array<int, array<string, mixed>> $failedRecords
     */
    public function __construct(public readonly array $results, public readonly array $failedRecords)
    {
    }
}
