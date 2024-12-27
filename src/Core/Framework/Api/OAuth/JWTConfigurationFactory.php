<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Log\Package;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256 as Hmac256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

/**
 * @internal
 */
#[Package('core')]
class JWTConfigurationFactory
{
    public static function createJWTConfiguration(
    ): Configuration {
        return self::createUsingAppSecret();
    }

    public static function createUsingAppSecret(): Configuration
    {
        /** @var non-empty-string $secret */
        $secret = (string) EnvironmentHelper::getVariable('APP_SECRET');
        $key = InMemory::plainText($secret);

        $configuration = Configuration::forSymmetricSigner(
            new Hmac256(),
            $key
        );

        $clock = new SystemClock(new \DateTimeZone(\date_default_timezone_get()));

        $configuration->setValidationConstraints(
            new SignedWith(new Hmac256(), $key),
            new LooseValidAt($clock, null),
        );

        return $configuration;
    }
}
