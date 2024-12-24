<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Checkers;

use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Update\Checkers\LicenseCheck;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(LicenseCheck::class)]
class LicenseCheckTest extends TestCase
{
    public function testLicenseIsValidWithoutLicenseHost(): void
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn(null);

        $licenseCheck = new LicenseCheck($systemConfig, $this->createMock(StoreClient::class));

        $validationResult = $licenseCheck->check()->jsonSerialize();

        static::assertTrue($validationResult['result']);
    }

    public function testIsValid(): void
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn('licensehost.test');

        $storeClient = $this->createMock(StoreClient::class);
        $storeClient->method('isShopUpgradeable')->willReturn(true);

        $licenseCheck = new LicenseCheck($systemConfig, $storeClient);
        $validationResult = $licenseCheck->check()->jsonSerialize();

        static::assertTrue($validationResult['result']);
    }

    public function testIsInvalid(): void
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('get')->willReturn('licensehost.test');

        $storeClient = $this->createMock(StoreClient::class);
        $storeClient->method('isShopUpgradeable')->willReturn(false);

        $licenseCheck = new LicenseCheck($systemConfig, $storeClient);
        $validationResult = $licenseCheck->check()->jsonSerialize();

        static::assertFalse($validationResult['result']);
    }
}
