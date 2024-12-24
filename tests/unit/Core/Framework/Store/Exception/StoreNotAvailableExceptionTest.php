<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\StoreNotAvailableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StoreNotAvailableException::class)]
class StoreNotAvailableExceptionTest extends TestCase
{
    public function testGetErrorCode(): void
    {
        static::assertSame(
            'FRAMEWORK__STORE_NOT_AVAILABLE',
            (new StoreNotAvailableException())->getErrorCode()
        );
    }

    public function testGetStatusCode(): void
    {
        static::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            (new StoreNotAvailableException())->getStatusCode()
        );
    }

    public function testGetMessage(): void
    {
        static::assertSame(
            'Store is not available',
            (new StoreNotAvailableException())->getMessage()
        );
    }
}
