<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Exception\InvalidDocumentRendererException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvalidDocumentRendererException::class)]
class InvalidDocumentRendererExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidDocumentRendererException('foo');

        static::assertSame('Unable to find a document renderer with type "foo"', $exception->getMessage());
        static::assertSame('DOCUMENT__INVALID_RENDERER_TYPE', $exception->getErrorCode());
        static::assertSame(400, $exception->getStatusCode());
    }
}
