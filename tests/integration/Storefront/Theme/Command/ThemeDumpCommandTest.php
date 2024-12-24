<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\App\Source\SourceResolver;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\Util\StaticFilesystem;
use Cicada\Storefront\Theme\Command\ThemeDumpCommand;
use Cicada\Storefront\Theme\ConfigLoader\StaticFileConfigDumper;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\ThemeFileResolver;
use Cicada\Storefront\Theme\ThemeFilesystemResolver;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class ThemeDumpCommandTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private string $parentThemeId;

    private string $childThemeId;

    protected function tearDown(): void
    {
        static::getContainer()->get(SourceResolver::class)->reset();
    }

    public function testExecuteShouldResolveThemeInheritanceChainAndConsiderThemeIdArgument(): void
    {
        $this->setUpExampleThemes();

        $themeFileResolverMock = new ThemeFileResolverMock();

        $themeFilesystemResolver = $this->createMock(ThemeFilesystemResolver::class);
        $themeFilesystemResolver->expects(static::once())->method('getFilesystemForStorefrontConfig')->willReturn(new StaticFilesystem());

        $themeDumpCommand = new ThemeDumpCommand(
            $this->getPluginRegistryMock(),
            $themeFileResolverMock,
            static::getContainer()->get('theme.repository'),
            static::getContainer()->getParameter('kernel.project_dir'),
            $this->createMock(StaticFileConfigDumper::class),
            $themeFilesystemResolver
        );

        $commandTester = new CommandTester($themeDumpCommand);

        $commandTester->execute([
            'theme-id' => $this->childThemeId,
            'domain-url' => 'http://localhost/1/' . $this->childThemeId,
        ]);

        static::assertSame(['any' => 'expectedConfig'], $themeFileResolverMock->themeConfig->getThemeConfig());
    }

    #[DataProvider('getArguments')]
    public function testExecuteShouldSuccess(?string $themeId = null, ?string $domainUrl = null): void
    {
        $this->setUpExampleThemes($themeId);

        $themeFileResolverMock = new ThemeFileResolverMock();
        $themeFilesystemResolverMock = $this->createMock(ThemeFilesystemResolver::class);
        $themeFilesystemResolverMock->method('getFilesystemForStorefrontConfig')->willReturn(new StaticFilesystem());

        $themeDumpCommand = new ThemeDumpCommand(
            $this->getPluginRegistryMock(),
            $themeFileResolverMock,
            static::getContainer()->get('theme.repository'),
            static::getContainer()->getParameter('kernel.project_dir'),
            $this->createMock(StaticFileConfigDumper::class),
            $themeFilesystemResolverMock
        );

        $themeDumpCommand->setHelperSet(new HelperSet([new QuestionHelper()]));
        $commandTester = new CommandTester($themeDumpCommand);

        $userInput = [];

        if (!$themeId) {
            $userInput[] = 'Parent theme';
        }

        if (!$domainUrl) {
            $userInput[] = 'http://localhost/1/' . $this->parentThemeId;
        }

        $commandTester->setInputs($userInput);
        $commandTester->execute([
            'theme-id' => $themeId,
            'domain-url' => $domainUrl,
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteShouldSuccessWithoutInteraction(): void
    {
        $this->setUpExampleThemes();

        $themeFileResolverMock = new ThemeFileResolverMock();
        $themeFilesystemResolverMock = $this->createMock(ThemeFilesystemResolver::class);
        $themeFilesystemResolverMock->method('getFilesystemForStorefrontConfig')->willReturn(new StaticFilesystem());

        $themeDumpCommand = new ThemeDumpCommand(
            $this->getPluginRegistryMock(),
            $themeFileResolverMock,
            static::getContainer()->get('theme.repository'),
            static::getContainer()->getParameter('kernel.project_dir'),
            $this->createMock(StaticFileConfigDumper::class),
            $themeFilesystemResolverMock
        );
        $themeDumpCommand->setHelperSet(new HelperSet([new QuestionHelper()]));

        $commandTester = new CommandTester($themeDumpCommand);
        $commandTester->execute([], ['interactive' => false]);

        $commandTester->assertCommandIsSuccessful();
    }

    /**
     * @return list<array{themeId: string|null, domainUrl: string|null}>
     */
    public static function getArguments(): array
    {
        $themeId = Uuid::randomHex();

        return [
            [
                'themeId' => $themeId,
                'domainUrl' => null,
            ],
            [
                'themeId' => $themeId,
                'domainUrl' => 'http://localhost/1/' . $themeId,
            ],
            [
                'themeId' => null,
                'domainUrl' => 'http://localhost/2/' . $themeId,
            ],
            [
                'themeId' => null,
                'domainUrl' => null,
            ],
        ];
    }

    private function getPluginRegistryMock(): MockObject&StorefrontPluginRegistry
    {
        $storePluginConfiguration1 = new StorefrontPluginConfiguration('parentTheme');
        $storePluginConfiguration1->setThemeConfig([
            'any' => 'expectedConfig',
        ]);

        $storePluginConfiguration2 = new StorefrontPluginConfiguration('childTheme');
        $storePluginConfiguration2->setThemeConfig([
            'any' => 'unexpectedConfig',
        ]);

        $mock = $this->getMockBuilder(StorefrontPluginRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getConfigurations')
            ->willReturn(
                new StorefrontPluginConfigurationCollection([$storePluginConfiguration1, $storePluginConfiguration2])
            );

        return $mock;
    }

    private function setUpExampleThemes(?string $parentThemeId = null): void
    {
        $themeRepository = static::getContainer()->get('theme.repository');
        $themeSalesChannelRepository = static::getContainer()->get('theme_sales_channel.repository');
        $context = Context::createDefaultContext();

        $parentThemeId = $parentThemeId ?? Uuid::randomHex();
        $childId = Uuid::randomHex();

        $this->childThemeId = $childId;
        $this->parentThemeId = $parentThemeId;

        $themes = [
            $parentThemeId => Uuid::randomHex(),
            $childId => Uuid::randomHex(),
        ];

        $themeRepository->create(
            [
                [
                    'id' => $parentThemeId,
                    'name' => 'Parent theme',
                    'technicalName' => 'parentTheme',
                    'author' => 'test',
                    'active' => true,
                ],
                [
                    'id' => $childId,
                    'parentThemeId' => $parentThemeId,
                    'name' => 'Child theme',
                    'author' => 'test',
                    'active' => true,
                ],
            ],
            $context
        );

        foreach ($themes as $themeId => $salesChannelId) {
            $this->createSalesChannel([
                'id' => $salesChannelId,
                'domains' => [
                    [
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                        'url' => 'http://localhost/1/' . $themeId,
                    ],
                    [
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                        'url' => 'http://localhost/2/' . $themeId,
                    ],
                ],
            ]);

            $themeSalesChannelRepository->create([['themeId' => $themeId, 'salesChannelId' => $salesChannelId]], $context);
        }
    }
}

/**
 * @internal
 */
class ThemeFileResolverMock extends ThemeFileResolver
{
    public StorefrontPluginConfiguration $themeConfig;

    public function __construct()
    {
    }

    public function resolveFiles(
        StorefrontPluginConfiguration $themeConfig,
        StorefrontPluginConfigurationCollection $configurationCollection,
        bool $onlySourceFiles
    ): array {
        $this->themeConfig = $themeConfig;

        return [];
    }
}
