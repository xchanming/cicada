<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\Exception;

use Cicada\Core\Content\Cms\Exception\UnexpectedFieldConfigValueType;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(UnexpectedFieldConfigValueType::class)]
class UnexpectedFieldConfigValueTypeTest extends TestCase
{
    public function testUnexpectedFieldConfigValueType(): void
    {
        $exception = new UnexpectedFieldConfigValueType('name', 'string', 'int');
        static::assertSame('CONTENT__CMS_UNEXPECTED_VALUE_TYPE', $exception->getErrorCode());
        static::assertSame('Expected to load value of "name" with type "string", but value with type "int" given.', $exception->getMessage());
    }
}
