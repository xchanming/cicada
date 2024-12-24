<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\App\Template;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Template\TemplateLoader;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Core\Test\Stub\App\StaticSourceResolver;
use Cicada\Storefront\Framework\App\Template\IconTemplateLoader;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IconTemplateLoader::class)]
class IconTemplateLoaderTest extends TestCase
{
    private IconTemplateLoader $templateLoader;

    private Manifest $manifest;

    protected function setUp(): void
    {
        $this->manifest = Manifest::createFromXmlFile(__DIR__ . '/../../../Theme/fixtures/Apps/theme/manifest.xml');

        $sourceResolver = new StaticSourceResolver([
            'SwagTheme' => new Filesystem(__DIR__ . '/../../../Theme/fixtures/Apps/theme'),
        ]);

        $this->templateLoader = new IconTemplateLoader(
            new TemplateLoader($sourceResolver),
            new StorefrontPluginConfigurationFactory(
                $this->createMock(KernelPluginLoader::class),
                $sourceResolver
            ),
            $sourceResolver,
        );
    }

    public function testGetTemplatePathsForAppReturnsIconPaths(): void
    {
        $templates = $this->templateLoader->getTemplatePathsForApp($this->manifest);
        \sort($templates);

        static::assertEquals(
            ['app/storefront/src/assets/icon-pack/custom-icons/activity.svg', 'storefront/layout/header/logo.html.twig'],
            $templates
        );
    }

    public function testGetTemplateContentForAppReturnsIconPaths(): void
    {
        static::assertStringEqualsFile(
            __DIR__ . '/../../../Theme/fixtures/Apps/theme/Resources/app/storefront/src/assets/icon-pack/custom-icons/activity.svg',
            $this->templateLoader->getTemplateContent('app/storefront/src/assets/icon-pack/custom-icons/activity.svg', $this->manifest)
        );
    }
}
