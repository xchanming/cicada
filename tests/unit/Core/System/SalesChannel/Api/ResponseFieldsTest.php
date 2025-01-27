<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Api;

use Cicada\Core\System\SalesChannel\Api\ResponseFields;
use Cicada\Core\System\SalesChannel\SalesChannelException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ResponseFields::class)]
class ResponseFieldsTest extends TestCase
{
    public function testIsAllowedReturnsTrueWhenTypeNotSet(): void
    {
        $responseFields = new ResponseFields(null);
        static::assertTrue($responseFields->isAllowed('someType', 'someProperty'));
    }

    public function testIsAllowedThrowsExceptionWhenIncludesTypeIsNotArray(): void
    {
        $this->expectException(SalesChannelException::class);
        $responseFields = new ResponseFields(['someType' => 'notArray']);
        $responseFields->isAllowed('someType', 'someProperty');
    }

    public function testIsAllowedReturnsFalseWhenPropertyNotIncluded(): void
    {
        $responseFields = new ResponseFields(['someType' => ['anotherProperty']]);
        static::assertFalse($responseFields->isAllowed('someType', 'someProperty'));
    }

    public function testIsAllowedReturnsTrueWhenPropertyIsIncluded(): void
    {
        $responseFields = new ResponseFields(['someType' => ['someProperty']]);
        static::assertTrue($responseFields->isAllowed('someType', 'someProperty'));
    }

    public function testHasNestedReturnsTrueWhenPropertyHasPrefix(): void
    {
        $responseFields = new ResponseFields(['alias' => ['prefix.property']]);
        static::assertTrue($responseFields->hasNested('alias', 'prefix'));
    }

    public function testHasNestedReturnsFalseWhenPropertyDoesNotHavePrefix(): void
    {
        $responseFields = new ResponseFields(['alias' => ['otherprefix.property']]);
        static::assertFalse($responseFields->hasNested('alias', 'prefix'));
    }
}
