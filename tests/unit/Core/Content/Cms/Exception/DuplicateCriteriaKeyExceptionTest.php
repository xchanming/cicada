<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Cms\Exception\DuplicateCriteriaKeyException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(DuplicateCriteriaKeyException::class)]
class DuplicateCriteriaKeyExceptionTest extends TestCase
{
    public function testDuplicateCriteriaKeyException(): void
    {
        $exception = new DuplicateCriteriaKeyException('duplicated-key');
        static::assertSame('CONTENT__DUPLICATE_CRITERIA_KEY', $exception->getErrorCode());
        static::assertSame('The key "duplicated-key" is duplicated in the criteria collection.', $exception->getMessage());
    }
}
