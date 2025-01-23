<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\User;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\UserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('fundamentals@framework')]
#[CoversClass(UserException::class)]
class UserExceptionTest extends TestCase
{
    public function testSalesChannelNotFound(): void
    {
        $exception = UserException::salesChannelNotFound();

        static::assertInstanceOf(UserException::class, $exception);
        static::assertSame(Response::HTTP_PRECONDITION_FAILED, $exception->getStatusCode());
        static::assertSame(UserException::SALES_CHANNEL_NOT_FOUND, $exception->getErrorCode());
        static::assertSame('No sales channel found.', $exception->getMessage());
        static::assertEmpty($exception->getParameters());
    }
}
