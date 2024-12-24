<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Exception\InvalidFileGeneratorTypeException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvalidFileGeneratorTypeException::class)]
class InvalidFileGeneratorTypeExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidFileGeneratorTypeException('foo');

        static::assertSame('Unable to find a file generator with type "foo"', $exception->getMessage());
        static::assertSame('DOCUMENT__INVALID_FILE_GENERATOR_TYPE', $exception->getErrorCode());
        static::assertSame(400, $exception->getStatusCode());
    }
}
