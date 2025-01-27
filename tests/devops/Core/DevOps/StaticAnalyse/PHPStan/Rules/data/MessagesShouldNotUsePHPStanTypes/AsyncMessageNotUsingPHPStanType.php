<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\MessagesShouldNotUsePHPStanTypes;

use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

class AsyncMessageNotUsingPHPStanType implements AsyncMessageInterface
{
    public function __construct(
        public readonly array $primaryKeys
    ) {
    }
}
