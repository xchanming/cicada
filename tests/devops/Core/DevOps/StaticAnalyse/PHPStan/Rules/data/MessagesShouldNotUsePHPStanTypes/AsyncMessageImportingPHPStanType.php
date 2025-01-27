<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\MessagesShouldNotUsePHPStanTypes;

use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * @phpstan-import-type PrimaryKeyList from AsyncMessageUsingPHPStanType
 */
class AsyncMessageImportingPHPStanType implements AsyncMessageInterface
{
    /**
     * @param PrimaryKeyList $primaryKeys
     */
    public function __construct(
        public readonly array $primaryKeys
    ) {
    }
}
