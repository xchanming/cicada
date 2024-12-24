<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Plugin;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Plugin\PluginExtractor;
use Cicada\Core\Framework\Plugin\PluginManagementService;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\SystemConfig\Exception\XmlParsingException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class PluginExtractorTest extends TestCase
{
    use KernelTestBehaviour;

    protected ContainerInterface $container;

    private Filesystem $filesystem;

    private PluginExtractor $extractor;

    protected function setUp(): void
    {
        $this->container = static::getContainer();
        $this->filesystem = $this->container->get(Filesystem::class);
        $this->extractor = new PluginExtractor(
            [
                PluginManagementService::PLUGIN => __DIR__ . '/_fixtures/plugins',
                PluginManagementService::APP => __DIR__ . '/_fixtures/apps',
            ],
            $this->filesystem
        );
    }

    public function testExtractPlugin(): void
    {
        $this->filesystem->copy(__DIR__ . '/_fixtures/archives/SwagFashionTheme.zip', __DIR__ . '/_fixtures/SwagFashionTheme.zip');

        $archive = __DIR__ . '/_fixtures/SwagFashionTheme.zip';

        $this->extractor->extract($archive, false, PluginManagementService::PLUGIN);

        static::assertFileExists(__DIR__ . '/_fixtures/plugins/SwagFashionTheme');
        static::assertFileExists(__DIR__ . '/_fixtures/plugins/SwagFashionTheme/SwagFashionTheme.php');

        $this->filesystem->remove(__DIR__ . '/_fixtures/plugins/SwagFashionTheme');
    }

    public function testExtractWithInvalidAppManifest(): void
    {
        $this->filesystem->copy(__DIR__ . '/_fixtures/archives/InvalidManifestShippingApp.zip', __DIR__ . '/_fixtures/TestShippingApp.zip');

        $archive = __DIR__ . '/_fixtures/TestShippingApp.zip';

        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(AppException::class);
        } else {
            $this->expectException(XmlParsingException::class);
        }

        $this->expectExceptionMessage('Unable to parse file "TestShippingApp/manifest.xml". Message: deliveryTime must not be empty');

        $this->extractor->extract($archive, false, PluginManagementService::APP);

        static::assertFileDoesNotExist(__DIR__ . '/_fixtures/apps/TestShippingApp');
    }
}
