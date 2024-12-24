<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Util\Exception\ComparatorException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(ComparatorException::class)]
class ComparatorExceptionTest extends TestCase
{
    public function testOperatorNotSupported(): void
    {
        $e = ComparatorException::operatorNotSupported('test');

        static::assertEquals(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
        static::assertEquals('CONTENT__OPERATOR_NOT_SUPPORTED', $e->getErrorCode());
        static::assertEquals('Operator "test" is not supported.', $e->getMessage());
    }
}
