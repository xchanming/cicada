<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Adapter\Filesystem\MemoryFilesystemAdapter;
use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInputFactory;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\Source\SourceResolver;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Feature\FeatureException;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Kernel;
use Cicada\Core\System\SystemConfig\Service\AppConfigReader;
use Cicada\Core\System\SystemConfig\Service\ConfigurationService;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\SystemConfig\Util\ConfigReader;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Event\ThemeCompilerConcatenatedStylesEvent;
use Cicada\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent;
use Cicada\Storefront\Theme\MD5ThemePathBuilder;
use Cicada\Storefront\Theme\ScssPhpCompiler;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\StorefrontPluginRegistryInterface;
use Cicada\Storefront\Theme\Subscriber\ThemeCompilerEnrichScssVarSubscriber;
use Cicada\Storefront\Theme\ThemeCompiler;
use Cicada\Storefront\Theme\ThemeFileResolver;
use Cicada\Storefront\Theme\ThemeFilesystemResolver;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\MockThemeCompilerConcatenatedSubscriber;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\MockThemeVariablesSubscriber;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\SimplePlugin\SimplePlugin;
use Doctrine\DBAL\Exception;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBus;

/**
 * @internal
 */
#[CoversClass(ThemeCompiler::class)]
class ThemeCompilerTest extends TestCase
{
    use AppSystemTestBehaviour;
    use DatabaseTransactionBehaviour;
    use EnvTestBehaviour;
    use KernelTestBehaviour;

    private ThemeCompiler $themeCompiler;

    private ThemeCompiler $themeCompilerAutoPrefix;

    private string $mockSalesChannelId;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $themeFileResolver = static::getContainer()->get(ThemeFileResolver::class);
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');

        // Avoid filesystem operations
        $mockFilesystem = $this->createMock(Filesystem::class);

        $this->mockSalesChannelId = '98432def39fc4624b33213a56b8c944d';

        $this->themeCompiler = new ThemeCompiler(
            $mockFilesystem,
            $mockFilesystem,
            new CopyBatchInputFactory(),
            $themeFileResolver,
            true,
            $this->eventDispatcher,
            static::getContainer()->get(ThemeFilesystemResolver::class),
            ['theme' => new UrlPackage(['http://localhost'], new EmptyVersionStrategy())],
            static::getContainer()->get(CacheInvalidator::class),
            $this->createMock(LoggerInterface::class),
            new MD5ThemePathBuilder(),
            static::getContainer()->get(ScssPhpCompiler::class),
            new MessageBus(),
            0,
            false
        );

        $this->themeCompilerAutoPrefix = new ThemeCompiler(
            $mockFilesystem,
            $mockFilesystem,
            new CopyBatchInputFactory(),
            $themeFileResolver,
            true,
            $this->eventDispatcher,
            static::getContainer()->get(ThemeFilesystemResolver::class),
            ['theme' => new UrlPackage(['http://localhost'], new EmptyVersionStrategy())],
            static::getContainer()->get(CacheInvalidator::class),
            $this->createMock(LoggerInterface::class),
            new MD5ThemePathBuilder(),
            static::getContainer()->get(ScssPhpCompiler::class),
            new MessageBus(),
            0,
            true
        );
    }

    protected function tearDown(): void
    {
        static::getContainer()->get(SourceResolver::class)->reset();
        static::getContainer()->get(ActiveAppsLoader::class)->reset();
    }

    public function testVariablesArrayConvertsToNonAssociativeArrayWithValidScssSyntax(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $formatVariables = $themeCompilerReflection->getMethod('formatVariables');
        $formatVariables->setAccessible(true);

        $variables = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
        ];

        $actual = $formatVariables->invoke($this->themeCompiler, $variables);

        $expected = [
            '$sw-color-brand-primary: #008490;',
            '$sw-color-brand-secondary: #526e7f;',
            '$sw-border-color: #bcc1c7;',
        ];

        static::assertSame($expected, $actual);
    }

    public function testDumpVariablesFindsConfigFieldsAndReturnsStringWithScssVariables(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $dumpVariables = $themeCompilerReflection->getMethod('dumpVariables');
        $dumpVariables->setAccessible(true);

        $mockConfig = [
            'fields' => [
                'sw-color-brand-primary' => [
                    'name' => 'sw-color-brand-primary',
                    'type' => 'color',
                    'value' => '#008490',
                ],
                'sw-color-brand-secondary' => [
                    'name' => 'sw-color-brand-secondary',
                    'type' => 'color',
                    'value' => '#526e7f',
                ],
                'sw-border-color' => [
                    'name' => 'sw-border-color',
                    'type' => 'color',
                    'value' => '#bcc1c7',
                ],
                'sw-custom-header' => [
                    'name' => 'sw-custom-header',
                    'type' => 'checkbox',
                    'value' => false,
                ],
                'sw-custom-footer' => [
                    'name' => 'sw-custom-header',
                    'type' => 'checkbox',
                    'value' => true,
                ],
                'sw-custom-cart' => [
                    'name' => 'sw-custom-header',
                    'type' => 'switch',
                    'value' => false,
                ],
                'sw-custom-product-box' => [
                    'name' => 'sw-custom-header',
                    'type' => 'switch',
                    'value' => true,
                ],
                'sw-multi-test' => [
                    'name' => 'sw-multi-test',
                    'type' => 'text',
                    'value' => [
                        'top',
                        'bottom',
                    ],
                    'custom' => [
                        'componentName' => 'sw-multi-select',
                        'options' => [
                            [
                                'value' => 'bottom',
                            ],
                            [
                                'value' => 'top',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actual = $dumpVariables->invoke($this->themeCompiler, $mockConfig, 'themeId', $this->mockSalesChannelId, Context::createDefaultContext());

        $expected = <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Storefront\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-color-brand-primary: #008490;
\$sw-color-brand-secondary: #526e7f;
\$sw-border-color: #bcc1c7;
\$sw-custom-header: 0;
\$sw-custom-footer: 1;
\$sw-custom-cart: 0;
\$sw-custom-product-box: 1;
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL;

        static::assertSame($expected, $actual);
    }

    public function testDumpVariablesIgnoresFieldsWithScssConfigPropertySetToFalse(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $dumpVariables = $themeCompilerReflection->getMethod('dumpVariables');
        $dumpVariables->setAccessible(true);

        $mockConfig = [
            'fields' => [
                'sw-color-brand-primary' => [
                    'name' => 'sw-color-brand-primary',
                    'type' => 'color',
                    'value' => '#008490',
                ],
                'sw-color-brand-secondary' => [
                    'name' => 'sw-color-brand-secondary',
                    'type' => 'color',
                    'value' => '#526e7f',
                ],
                // Prevent adding field as sass variable
                'sw-ignore-me' => [
                    'name' => 'sw-border-color',
                    'type' => 'text',
                    'value' => 'Foo bar',
                    'scss' => false,
                ],
            ],
        ];

        $actual = $dumpVariables->invoke($this->themeCompiler, $mockConfig, 'themeId', $this->mockSalesChannelId, Context::createDefaultContext());

        $expected = <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Storefront\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-color-brand-primary: #008490;
\$sw-color-brand-secondary: #526e7f;
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL;

        static::assertSame($expected, $actual);
    }

    public function testDumpVariablesHasNoConfigFieldsAndReturnsOnlyAssetUrl(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $dumpVariables = $themeCompilerReflection->getMethod('dumpVariables');
        $dumpVariables->setAccessible(true);

        // Config without `fields`
        $mockConfig = [
            'blocks' => [
                'themeColors' => [
                    'label' => [
                        'en-GB' => 'Theme colours',
                        'de-DE' => 'Theme-Farben',
                    ],
                ],
                'typography' => [
                    'label' => [
                        'en-GB' => 'Typography',
                        'de-DE' => 'Typografie',
                    ],
                ],
            ],
        ];

        $actual = $dumpVariables->invoke($this->themeCompiler, $mockConfig, 'themeId', $this->mockSalesChannelId, Context::createDefaultContext());

        static::assertSame('// ATTENTION! This file is auto generated by the Cicada\Storefront\Theme\ThemeCompiler and should not be edited.

$theme-id: themeId;
$sw-asset-theme-url: \'http://localhost\';
', $actual);
    }

    public function testScssVariablesMayHaveZeroValueButNotNull(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $dumpVariables = $themeCompilerReflection->getMethod('dumpVariables');
        $dumpVariables->setAccessible(true);

        $mockConfig = [
            'fields' => [
                'sw-zero-margin' => [
                    'name' => 'sw-null-margin',
                    'type' => 'text',
                    'value' => 0,
                ],
                'sw-null-margin' => [
                    'name' => 'sw-null-margin',
                    'type' => 'text',
                    'value' => null,
                ],
                'sw-unset-margin' => [
                    'name' => 'sw-unset-margin',
                    'type' => 'text',
                ],
                'sw-empty-margin' => [
                    'name' => 'sw-unset-margin',
                    'type' => 'text',
                    'value' => '',
                ],
            ],
        ];

        $actual = $dumpVariables->invoke($this->themeCompiler, $mockConfig, 'themeId', $this->mockSalesChannelId, Context::createDefaultContext());

        $expected = <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Storefront\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-zero-margin: 0;
\$sw-null-margin: 0;
\$sw-unset-margin: 0;
\$sw-empty-margin: 0;
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL;

        static::assertSame($expected, $actual);
    }

    public function testScssVariablesEventAddsNewVariablesToArray(): void
    {
        $subscriber = new MockThemeVariablesSubscriber(static::getContainer()->get(SystemConfigService::class));

        $variables = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
        ];

        $event = new ThemeCompilerEnrichScssVariablesEvent($variables, $this->mockSalesChannelId, Context::createDefaultContext());
        $subscriber->onAddVariables($event);

        $actual = $event->getVariables();

        $expected = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
            'mock-variable-black' => '#000000',
            'mock-variable-special' => '\'Special value with quotes\'',
        ];

        static::assertSame($expected, $actual);
    }

    public function testConcanatedStylesEventPassThru(): void
    {
        $subscriber = new MockThemeCompilerConcatenatedSubscriber();

        $styles = 'body {}';

        $event = new ThemeCompilerConcatenatedStylesEvent($styles, $this->mockSalesChannelId);
        $subscriber->onGetConcatenatedStyles($event);
        $actual = $event->getConcatenatedStyles();

        $expected = $styles . MockThemeCompilerConcatenatedSubscriber::STYLES_CONCAT;

        static::assertEquals($expected, $actual);
    }

    public function testDBException(): void
    {
        $configService = $this->getConfigurationServiceDbException(
            [
                new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'),
            ]
        );

        $storefrontPluginRegistry = $this->getStorefrontPluginRegistry(
            [
                new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'),
            ]
        );

        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($configService, $storefrontPluginRegistry);
        $stderr = fopen('php://stderr', 'w');

        $subscriber->enrichExtensionVars(new ThemeCompilerEnrichScssVariablesEvent([], TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    /**
     * Theme compilation should be able to run without a database connection.
     */
    public function testCompileWithoutDB(): void
    {
        $this->stopTransactionAfter();
        $this->setEnvVars(['DATABASE_URL' => 'mysql://user:no@mysql:3306/test_db']);
        KernelLifecycleManager::bootKernel(false, 'noDB');
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $testFolder = $projectDir . '/bla';

        if (!file_exists($testFolder)) {
            mkdir($testFolder);
        }

        $resolver = $this->createMock(ThemeFileResolver::class);
        $resolver->method('resolveFiles')->willReturn([ThemeFileResolver::SCRIPT_FILES => new FileCollection(), ThemeFileResolver::STYLE_FILES => new FileCollection()]);

        $config = new StorefrontPluginConfiguration('test');
        $config->setAssetPaths(['bla']);

        $fs = new Filesystem(new MemoryFilesystemAdapter());
        $tmpFs = new Filesystem(new MemoryFilesystemAdapter());

        $compiler = new ThemeCompiler(
            $fs,
            $tmpFs,
            new CopyBatchInputFactory(),
            $resolver,
            true,
            static::getContainer()->get('event_dispatcher'),
            $this->createMock(ThemeFilesystemResolver::class),
            [],
            $this->createMock(CacheInvalidator::class),
            $this->createMock(LoggerInterface::class),
            new MD5ThemePathBuilder(),
            static::getContainer()->get(ScssPhpCompiler::class),
            new MessageBus(),
            0,
            false
        );

        try {
            $compiler->compileTheme(
                TestDefaults::SALES_CHANNEL,
                'test',
                $config,
                new StorefrontPluginConfigurationCollection(),
                true,
                Context::createDefaultContext()
            );
        } catch (\Throwable $throwable) {
            static::fail('ThemeCompiler->compile() should be executable without a database connection. But following Excpetion was thrown: ' . $throwable->getMessage());
        } finally {
            $this->resetEnvVars();
            KernelLifecycleManager::bootKernel(true);
            $this->startTransactionBefore();
            rmdir($testFolder);
        }
    }

    public function testOutputsPluginCss(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/fixtures/Apps/noThemeCustomCss');
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $compileStyles = $themeCompilerReflection->getMethod('compileStyles');
        $compileStyles->setAccessible(true);

        $testScss = <<<PHP_EOL
.test-selector-plugin {
        background: \$simple-plugin-backgroundcolor;
        color: \$simple-plugin-fontcolor;
        border: \$simple-plugin-bordercolor;
}
.test-selector-app {
        background: \$no-theme-custom-css-backgroundcolor;
        color: \$no-theme-custom-css-fontcolor;
        border: \$no-theme-custom-css-bordercolor;
}

PHP_EOL;

        $expectedCssOutput = <<<PHP_EOL
.test-selector-plugin {
\tbackground: #fff;
\tcolor: #eee;
\tborder: 0;
}

.test-selector-app {
\tbackground: #aaa;
\tcolor: #eee;
\tborder: 0;
}
PHP_EOL;

        $expectedCssOutputNoAutoPrefix = <<<PHP_EOL
.test-selector-plugin {
  background: #fff;
  color: #eee;
  border: 0;
}
.test-selector-app {
  background: #aaa;
  color: #eee;
  border: 0;
}
PHP_EOL;

        $configService = $this->getConfigurationService(
            [
                new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'),
            ]
        );

        $storefrontPluginRegistry = $this->getStorefrontPluginRegistry(
            [
                new SimplePlugin(true, __DIR__ . '/fixtures/SimplePlugin'),
            ]
        );

        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($configService, $storefrontPluginRegistry);

        $this->eventDispatcher->addSubscriber($subscriber);

        /** @var SystemConfigService $sysConfService */
        $sysConfService = static::getContainer()->get(SystemConfigService::class);
        $sysConfService->set('SimplePlugin.config.simplePluginBackgroundcolor', '#fff');
        $sysConfService->set('SwagNoThemeCustomCss.config.noThemeCustomCssBackGroundcolor', '#aaa');

        try {
            $actual = $compileStyles->invoke(
                $this->themeCompiler,
                $testScss,
                new StorefrontPluginConfiguration('test'),
                [],
                '1337',
                'themeId',
                Context::createDefaultContext()
            );
        } finally {
            $this->eventDispatcher->removeSubscriber($subscriber);
        }

        static::assertSame($expectedCssOutputNoAutoPrefix, trim((string) $actual));

        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($configService, $storefrontPluginRegistry);

        $this->eventDispatcher->addSubscriber($subscriber);

        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(FeatureException::class);
            $this->expectExceptionMessage('Tried to access deprecated functionality: Autoprefixer is deprecated and will be removed without replacement, including the config storefront.theme.auto_prefix_css.');
        }

        try {
            $actual = $compileStyles->invoke(
                $this->themeCompilerAutoPrefix,
                $testScss,
                new StorefrontPluginConfiguration('test'),
                [],
                '1337',
                'themeId',
                Context::createDefaultContext()
            );
        } finally {
            $this->eventDispatcher->removeSubscriber($subscriber);
        }

        static::assertSame($expectedCssOutput, trim((string) $actual));
    }

    public function testOutputsOnlyExpectedCssWhenUsingFeatureFlagFunction(): void
    {
        if (EnvironmentHelper::getVariable('FEATURE_ALL')) {
            static::markTestSkipped('Skipped because fixture feature `FEATURE_ALL` should be false.');
        }

        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $compileStyles = $themeCompilerReflection->getMethod('compileStyles');
        $compileStyles->setAccessible(true);

        Feature::registerFeatures([
            'FEATURE_NEXT_1' => ['default' => true],
            'FEATURE_NEXT_2' => ['default' => false],
            'V6_5_0_0' => ['default' => false],
        ]);

        // Ensure feature flag mixin SCSS file is given
        $featureMixin = file_get_contents(
            __DIR__ . '/../../../../src/Storefront/Resources/app/storefront/src/scss/abstract/functions/feature.scss'
        );

        $testScss = <<<PHP_EOL
.test-selector {
    @if feature('FEATURE_NEXT_1') {
        background: yellow;
    } @else {
        background: blue;
    }
    color: red;
}

@if feature('FEATURE_NEXT_2') {
    .not-here {
        display: none;
        // Should not throw when undefined var is behind inactive flag
        color: \$undefined-variable;
    }
}
PHP_EOL;

        $expectedCssOutput = <<<PHP_EOL
/*
Helper function to check for active feature flags.
==================================================
The `\$sw-features` variable contains a SCSS map of the current feature config.
The variable is injected automatically via ThemeCompiler.php and webpack.config.js.

Example:
@if feature('FEATURE_NEXT_1234') {
    // ...
}
*/
.test-selector {
  background: yellow;
  color: red;
}
PHP_EOL;

        $actual = $compileStyles->invoke(
            $this->themeCompiler,
            $featureMixin . $testScss,
            new StorefrontPluginConfiguration('test'),
            [],
            '1337',
            'themeId',
            Context::createDefaultContext()
        );

        static::assertSame(trim($expectedCssOutput), trim((string) $actual));
    }

    public function testVendorImportFiles(): void
    {
        $themeCompilerReflection = new \ReflectionClass(ThemeCompiler::class);
        $compileStyles = $themeCompilerReflection->getMethod('compileStyles');
        $compileStyles->setAccessible(true);

        $testScss = <<<PHP_EOL
@import '~vendor/library.min'; // Test import for plain CSS without extension
@import '~vendor/library.min.css'; // Test import for plain CSS with explicit extension (deprecated)
@import '~vendor/another-library'; // Test import of SCSS module
@import '~vendor/another-library.scss'; // Test import of SCSS module with explicit extension
PHP_EOL;

        $expectedCssOutput = <<<PHP_EOL
.plain-css-from-library {
  color: red;
}
.plain-css-from-library {
  color: red;
}
.another-lib {
  color: #0d9c0d;
}
.another-lib {
  color: #0d9c0d;
}
PHP_EOL;

        $actual = $compileStyles->invoke(
            $this->themeCompiler,
            $testScss,
            new StorefrontPluginConfiguration('test'),
            [
                'vendor' => __DIR__ . '/fixtures/ThemeWithScssVendorImports/Storefront/Resources/app/storefront/vendor',
            ],
            '1337',
            'themeId',
            Context::createDefaultContext()
        );

        static::assertSame(trim($expectedCssOutput), trim((string) $actual));
    }

    /**
     * @param array<int, Plugin> $plugins
     */
    private function getConfigurationService(array $plugins): ConfigurationService
    {
        return new ConfigurationService(
            $plugins,
            new ConfigReader(),
            static::getContainer()->get(AppConfigReader::class),
            static::getContainer()->get('app.repository'),
            static::getContainer()->get(SystemConfigService::class)
        );
    }

    /**
     * @param array<int, Plugin> $plugins
     */
    private function getConfigurationServiceDbException(array $plugins): ConfigurationService
    {
        return new ConfigurationServiceException(
            $plugins,
            new ConfigReader(),
            static::getContainer()->get(AppConfigReader::class),
            static::getContainer()->get('app.repository'),
            static::getContainer()->get(SystemConfigService::class)
        );
    }

    /**
     * @param array<int, Plugin> $plugins
     */
    private function getStorefrontPluginRegistry(array $plugins): StorefrontPluginRegistryInterface
    {
        $kernel = $this->createMock(Kernel::class);
        $kernel->expects(static::any())
            ->method('getBundles')
            ->willReturn($plugins);

        return new StorefrontPluginRegistry(
            $kernel,
            static::getContainer()->get(StorefrontPluginConfigurationFactory::class),
            static::getContainer()->get(ActiveAppsLoader::class)
        );
    }
}

/**
 * @internal
 */
class ConfigurationServiceException extends ConfigurationService
{
    /**
     * @throws Exception
     */
    public function checkConfiguration(string $domain, Context $context): bool
    {
        throw Exception::invalidPlatformType('any');
    }
}
