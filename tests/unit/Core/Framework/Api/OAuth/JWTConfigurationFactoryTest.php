<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\OAuth;

use Cicada\Core\Framework\Api\OAuth\JWTConfigurationFactory;
use Lcobucci\JWT\Signer\Hmac\Sha256 as Hmac256;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(JWTConfigurationFactory::class)]
class JWTConfigurationFactoryTest extends TestCase
{
    public function testCreateFromAppEnv(): void
    {
        $config = JWTConfigurationFactory::createJWTConfiguration();

        static::assertInstanceOf(Hmac256::class, $config->signer());
    }
}
