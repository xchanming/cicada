<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Api\ExtensionStoreDataController;
use Cicada\Core\Framework\Test\Store\ExtensionBehaviour;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class ExtensionStoreDataControllerTest extends TestCase
{
    use ExtensionBehaviour;
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private ExtensionStoreDataController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = static::getContainer()->get(ExtensionStoreDataController::class);
    }

    public function testInstalled(): void
    {
        $this->installApp(__DIR__ . '/../_fixtures/TestApp');

        $this->getStoreRequestHandler()->reset();
        $this->getStoreRequestHandler()->append(new Response(200, [], '[]'));

        $response = $this->controller->getInstalledExtensions($this->createAdminStoreContext());
        $data = json_decode($response->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);

        static::assertNotEmpty($data);
        static::assertContains('TestApp', array_column($data, 'name'));
    }
}
