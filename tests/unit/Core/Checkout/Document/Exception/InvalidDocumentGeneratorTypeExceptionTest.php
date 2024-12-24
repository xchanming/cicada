<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Exception;

use Cicada\Core\Checkout\Document\Exception\InvalidDocumentGeneratorTypeException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvalidDocumentGeneratorTypeException::class)]
class InvalidDocumentGeneratorTypeExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidDocumentGeneratorTypeException(500, 'TEST', 'FOO');

        static::assertSame('FOO', $exception->getMessage());
        static::assertSame('TEST', $exception->getErrorCode());
        static::assertSame(500, $exception->getStatusCode());
    }
}
