<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Lifecycle\Registration;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Exception\AppRegistrationException;
use Cicada\Core\Framework\App\Lifecycle\Registration\HandshakeFactory;
use Cicada\Core\Framework\App\Lifecycle\Registration\PrivateHandshake;
use Cicada\Core\Framework\App\Lifecycle\Registration\StoreHandshake;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Kernel;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\AppSystemTestBehaviour;

/**
 * @internal
 */
class HandshakeFactoryTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    public function testManifestWithSecretProducesAPrivateHandshake(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../Manifest/_fixtures/minimal/manifest.xml');

        $shopUrl = 'test.shop.com';

        $factory = new HandshakeFactory(
            $shopUrl,
            static::getContainer()->get(ShopIdProvider::class),
            static::getContainer()->get(StoreClient::class),
            Kernel::CICADA_FALLBACK_VERSION
        );

        $handshake = $factory->create($manifest);

        static::assertInstanceOf(PrivateHandshake::class, $handshake);
    }

    public function testThrowsAppRegistrationExceptionIfAppUrlChangeWasDetected(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/../../Manifest/_fixtures/minimal');
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../Manifest/_fixtures/minimal/manifest.xml');

        $shopUrl = 'test.shop.com';

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY, [
            'app_url' => 'https://test.com',
            'value' => Uuid::randomHex(),
        ]);

        $factory = new HandshakeFactory(
            $shopUrl,
            static::getContainer()->get(ShopIdProvider::class),
            static::getContainer()->get(StoreClient::class),
            Kernel::CICADA_FALLBACK_VERSION
        );

        static::expectException(AppRegistrationException::class);
        $factory->create($manifest);
    }

    public function testManifestWithoutSecretProducesAStoreHandshake(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../Manifest/_fixtures/private/manifest.xml');

        $shopUrl = 'test.shop.com';

        $factory = new HandshakeFactory(
            $shopUrl,
            static::getContainer()->get(ShopIdProvider::class),
            static::getContainer()->get(StoreClient::class),
            Kernel::CICADA_FALLBACK_VERSION
        );

        $handshake = $factory->create($manifest);

        static::assertInstanceOf(StoreHandshake::class, $handshake);
    }
}
