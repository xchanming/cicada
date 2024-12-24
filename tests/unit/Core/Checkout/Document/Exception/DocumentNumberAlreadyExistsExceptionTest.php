<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Exception\DocumentNumberAlreadyExistsException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DocumentNumberAlreadyExistsException::class)]
class DocumentNumberAlreadyExistsExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new DocumentNumberAlreadyExistsException('test');

        static::assertSame('Document number test has already been allocated.', $exception->getMessage());
        static::assertSame('DOCUMENT__NUMBER_ALREADY_EXISTS', $exception->getErrorCode());
        static::assertSame(400, $exception->getStatusCode());
    }
}
