<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\InvalidVariantIdException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvalidVariantIdException::class)]
class InvalidVariantIdExceptionTest extends TestCase
{
    public function testGetErrorCode(): void
    {
        static::assertSame(
            'FRAMEWORK__INVALID_VARIANT_ID',
            (new InvalidVariantIdException())->getErrorCode()
        );
    }

    public function testGetStatusCode(): void
    {
        static::assertSame(
            Response::HTTP_BAD_REQUEST,
            (new InvalidVariantIdException())->getStatusCode()
        );
    }

    public function testGetMessage(): void
    {
        static::assertSame(
            'The variant id must be an non empty numeric value.',
            (new InvalidVariantIdException())->getMessage()
        );
    }
}
