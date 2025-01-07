<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\ContextTokenResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(ContextTokenResponse::class)]
class ContextTokenResponseTest extends TestCase
{
    public function testGetTokenFromResponseBody(): void
    {
        $token = 'sw-token-value';
        $response = new ContextTokenResponse($token);
        static::assertSame($token, $response->getToken());
    }

    public function testGetTokenFromHeader(): void
    {
        $token = 'sw-token-value';
        $response = new ContextTokenResponse($token);
        static::assertSame($token, $response->getToken());

        // It should be stored in a header instead
        static::assertSame($token, $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN));
    }
}
