<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginException;
use Cicada\Core\Framework\Store\Services\ExtensionDownloader;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[Package('checkout')]
class ExtensionDownloaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private ExtensionDownloader $extensionDownloader;

    protected function setUp(): void
    {
        $this->extensionDownloader = static::getContainer()->get(ExtensionDownloader::class);

        @mkdir(static::getContainer()->getParameter('kernel.app_dir'), 0777, true);
    }

    public function testDownloadExtension(): void
    {
        $this->getStoreRequestHandler()->reset();
        $this->getStoreRequestHandler()->append(new Response(200, [], '{"location": "http://localhost/my.zip", "type": "app"}'));
        $this->getStoreRequestHandler()->append(new Response(200, [], (string) file_get_contents(__DIR__ . '/../_fixtures/TestApp.zip')));

        $context = $this->createAdminStoreContext();

        $this->extensionDownloader->download('TestApp', $context);
        $expectedLocation = static::getContainer()->getParameter('kernel.app_dir') . '/TestApp';

        static::assertFileExists($expectedLocation);
        (new Filesystem())->remove($expectedLocation);
    }

    public function testDownloadExtensionServerNotReachable(): void
    {
        $this->getStoreRequestHandler()->reset();
        $this->getStoreRequestHandler()->append(new Response(200, [], '{"location": "http://localhost/my.zip"}'));
        $this->getStoreRequestHandler()->append(new Response(500, [], ''));

        $context = $this->createAdminStoreContext();

        static::expectException(PluginException::class);
        static::expectExceptionMessage('Store is not available');
        $this->extensionDownloader->download('TestApp', $context);
    }

    public function testDownloadWhichIsAnComposerExtension(): void
    {
        static::expectException(StoreException::class);

        static::getContainer()->get('plugin.repository')->create(
            [
                [
                    'name' => 'TestApp',
                    'label' => 'TestApp',
                    'baseClass' => 'TestApp',
                    'path' => static::getContainer()->getParameter('kernel.project_dir') . '/vendor/swag/TestApp',
                    'autoload' => [],
                    'version' => '1.0.0',
                    'managedByComposer' => true,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->extensionDownloader->download('TestApp', Context::createDefaultContext(new AdminApiSource(Uuid::randomHex())));
    }

    public function testDownloadExtensionWhichIsALocalComposerPlugin(): void
    {
        $this->getStoreRequestHandler()->reset();
        $this->getStoreRequestHandler()->append(new Response(200, [], '{"location": "http://localhost/my.zip", "type": "app"}'));
        $this->getStoreRequestHandler()->append(new Response(200, [], (string) file_get_contents(__DIR__ . '/../_fixtures/TestApp.zip')));

        $pluginPath = static::getContainer()->getParameter('kernel.plugin_dir') . '/TestApp';
        $projectPath = static::getContainer()->getParameter('kernel.project_dir');

        static::getContainer()->get('plugin.repository')->create(
            [
                [
                    'name' => 'TestApp',
                    'label' => 'TestApp',
                    'baseClass' => 'TestApp',
                    'path' => str_replace($projectPath . '/', '', $pluginPath),
                    'autoload' => [],
                    'version' => '1.0.0',
                    'managedByComposer' => true,
                ],
            ],
            Context::createDefaultContext()
        );

        $context = $this->createAdminStoreContext();

        $this->extensionDownloader->download('TestApp', $context);
        $expectedLocation = static::getContainer()->getParameter('kernel.app_dir') . '/TestApp';

        static::assertFileExists($expectedLocation);
        (new Filesystem())->remove($expectedLocation);
    }
}
