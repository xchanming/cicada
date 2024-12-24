<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Exception\InvalidDocumentException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvalidDocumentException::class)]
class InvalidDocumentExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidDocumentException('test');

        static::assertSame('The document with id "test" is invalid or could not be found.', $exception->getMessage());
        static::assertSame('DOCUMENT__INVALID_DOCUMENT_ID', $exception->getErrorCode());
        static::assertSame(400, $exception->getStatusCode());
    }
}
