<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\StoreLicenseDomainMissingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StoreLicenseDomainMissingException::class)]
class StoreLicenseDomainMissingExceptionTest extends TestCase
{
    public function testGetErrorCode(): void
    {
        static::assertSame(
            'FRAMEWORK__STORE_LICENSE_DOMAIN_IS_MISSING',
            (new StoreLicenseDomainMissingException())->getErrorCode()
        );
    }

    public function testGetStatusCode(): void
    {
        static::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            (new StoreLicenseDomainMissingException())->getStatusCode()
        );
    }

    public function testGetMessage(): void
    {
        static::assertSame(
            'Store license domain is missing',
            (new StoreLicenseDomainMissingException())->getMessage()
        );
    }
}
