<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Util;

use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(AccessKeyHelper::class)]
class AccessKeyHelperTest extends TestCase
{
    #[DataProvider('mappingIdentifier')]
    public function testGenerateAccessKeyWithUserIdentifier(string $origin, string $identifier): void
    {
        $accessKey = AccessKeyHelper::generateAccessKey($identifier);
        static::assertStringContainsString($origin, $accessKey);
    }

    public function testGenerateAccessKeyWithInvalidIdentifier(): void
    {
        static::expectException(ApiException::class);
        static::expectExceptionMessage('Given identifier for access key is invalid.');
        AccessKeyHelper::generateAccessKey('invalid_identifier');
    }

    public function testGenerateOriginWithIntegrationIdentifier(): void
    {
        $accessKey = AccessKeyHelper::generateAccessKey('integration');
        $origin = AccessKeyHelper::getOrigin($accessKey);
        static::assertSame('integration', $origin);
    }

    public function testGenerateOriginWithInvalidAccessKey(): void
    {
        static::expectExceptionMessage('Access key is invalid and could not be identified.');
        static::expectException(ApiException::class);
        AccessKeyHelper::getOrigin('invalid_access_key');
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function mappingIdentifier(): array
    {
        return [
            ['SWUA', 'user'],
            ['SWIA', 'integration'],
            ['SWSC', 'sales-channel'],
            ['SWPE', 'product-export'],
        ];
    }
}
