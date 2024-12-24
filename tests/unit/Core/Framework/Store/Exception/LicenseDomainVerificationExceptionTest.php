<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\LicenseDomainVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LicenseDomainVerificationException::class)]
class LicenseDomainVerificationExceptionTest extends TestCase
{
    public function testGetErrorCode(): void
    {
        static::assertSame(
            'FRAMEWORK__STORE_LICENSE_DOMAIN_VALIDATION_FAILED',
            (new LicenseDomainVerificationException('license.domain'))->getErrorCode()
        );
    }

    public function testGetStatusCode(): void
    {
        static::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            (new LicenseDomainVerificationException('license.domain'))->getStatusCode()
        );
    }

    public function testGetMessage(): void
    {
        static::assertSame(
            'License host verification failed for domain "license.domain."',
            (new LicenseDomainVerificationException('license.domain'))->getMessage()
        );
    }
}
