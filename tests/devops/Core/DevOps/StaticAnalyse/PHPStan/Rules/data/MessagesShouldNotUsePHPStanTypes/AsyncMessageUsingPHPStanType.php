<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\MessagesShouldNotUsePHPStanTypes;

use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * @phpstan-type PrimaryKeyList array<int, array<string, string>>
 */
class AsyncMessageUsingPHPStanType implements AsyncMessageInterface
{
    /**
     * @param PrimaryKeyList $primaryKeys
     */
    public function __construct(
        public readonly array $primaryKeys
    ) {
    }
}
