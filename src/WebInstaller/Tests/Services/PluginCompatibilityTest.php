<?php declare(strict_types=1);

namespace Cicada\WebInstaller\Tests\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\WebInstaller\Services\PluginCompatibility;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversClass(PluginCompatibility::class)]
class PluginCompatibilityTest extends TestCase
{
    private string $json;

    private string $installedJson;

    protected function setUp(): void
    {
        $this->json = __DIR__ . '/composer.json';
        $this->installedJson = __DIR__ . '/vendor/composer/installed.json';

        mkdir(__DIR__ . '/vendor/cicada', 0777, true);
        mkdir(__DIR__ . '/vendor/composer', 0777, true);
        mkdir(__DIR__ . '/custom/plugins/SwagCommercial', 0777, true);
        symlink(__DIR__ . '/custom/plugins/SwagCommercial', __DIR__ . '/vendor/cicada/commercial');
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove(
            [
                $this->json,
                $this->json . '.bak',
                __DIR__ . '/vendor',
                __DIR__ . '/custom']
        );
    }

    public function testIncompatibleCustomComposerPluginIsRemovedFromProject(): void
    {
        $this->projectDeps([
            'cicada/commercial' => '5.8.7',
            'cicada/core' => '~v6.5.0',
        ]);

        $this->pluginRequires([
            'cicada/core' => '~v6.5.8',
        ]);

        $compat = new PluginCompatibility(__DIR__ . '/composer.json', '6.6');
        $compat->removeIncompatible();

        static::assertEquals(
            [
                'require' => [
                    'cicada/core' => '~v6.5.0',
                ],
            ],
            json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true, flags: \JSON_THROW_ON_ERROR)
        );
    }

    public function testCompatibleCustomComposerPluginIsNotRemovedFromProject(): void
    {
        $this->projectDeps([
            'cicada/commercial' => '5.8.7',
            'cicada/core' => '~v6.5.0',
        ]);

        $this->pluginRequires([
            'cicada/core' => '~v6.5.8 || ^6.6',
        ]);

        $compat = new PluginCompatibility(__DIR__ . '/composer.json', '6.6');
        $compat->removeIncompatible();

        static::assertFileDoesNotExist(__DIR__ . '/composer.json.bak');

        static::assertEquals(
            [
                'require' => [
                    'cicada/commercial' => '5.8.7',
                    'cicada/core' => '~v6.5.0',
                ],
            ],
            json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true, flags: \JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param array<string, string> $deps
     */
    private function projectDeps(array $deps): void
    {
        file_put_contents($this->json, json_encode([
            'require' => $deps,
        ], \JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, string> $requires
     */
    private function pluginRequires(array $requires): void
    {
        file_put_contents($this->installedJson, json_encode([
            'packages' => [
                'cicada/commercial' => [
                    'name' => 'cicada/commercial',
                    'type' => 'cicada-platform-plugin',
                    'install-path' => '../cicada/commercial',
                    'require' => $requires,
                ],
            ],
        ], \JSON_THROW_ON_ERROR));
    }
}
