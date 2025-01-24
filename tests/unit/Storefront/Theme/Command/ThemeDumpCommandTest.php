<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Command;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Storefront\Theme\Command\ThemeDumpCommand;
use Cicada\Storefront\Theme\ConfigLoader\StaticFileConfigDumper;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistryInterface;
use Cicada\Storefront\Theme\ThemeEntity;
use Cicada\Storefront\Theme\ThemeFileResolver;
use Cicada\Storefront\Theme\ThemeFilesystemResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(ThemeDumpCommand::class)]
class ThemeDumpCommandTest extends TestCase
{
    private StorefrontPluginRegistryInterface&MockObject $pluginRegistry;

    private ThemeFileResolver&MockObject $themeFileResolver;

    private EntityRepository&MockObject $themeRepository;

    private ThemeFilesystemResolver&MockObject $themeFilesystemResolver;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->pluginRegistry = $this->createMock(StorefrontPluginRegistryInterface::class);
        $this->themeFileResolver = $this->createMock(ThemeFileResolver::class);
        $this->themeRepository = $this->createMock(EntityRepository::class);
        $staticFileConfigDumper = $this->createMock(StaticFileConfigDumper::class);
        $this->themeFilesystemResolver = $this->createMock(ThemeFilesystemResolver::class);

        $command = new ThemeDumpCommand(
            $this->pluginRegistry,
            $this->themeFileResolver,
            $this->themeRepository,
            './tests/unit/Storefront/Theme/fixtures',
            $staticFileConfigDumper,
            $this->themeFilesystemResolver
        );

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecutesSuccessfullyWithValidThemeName(): void
    {
        $themeEntity = new ThemeEntity();
        $themeEntity->setId('theme-id');
        $themeEntity->setTechnicalName('technical-name');
        $themeEntity->setName('Theme Name');

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(1);
        $searchResult->method('first')->willReturn($themeEntity);

        $this->themeRepository->method('search')->willReturn($searchResult);

        $this->pluginRegistry->method('getConfigurations')->willReturn(
            new StorefrontPluginConfigurationCollection([
                new StorefrontPluginConfiguration('technical-name'),
            ])
        );

        $this->themeFileResolver->method('resolveFiles')->willReturn(['resolved' => 'files']);
        $this->themeFilesystemResolver->method('getFilesystemForStorefrontConfig')->willReturn(
            new Filesystem('')
        );

        $this->commandTester->execute(
            ['domain-url' => 'http://example.com'],
            ['theme-name' => 'technical-name']
        );

        static::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecutesSuccessfullyWithValidThemeId(): void
    {
        $themeEntity = new ThemeEntity();
        $themeEntity->setId('theme-id');
        $themeEntity->setTechnicalName('technical-name');
        $themeEntity->setName('Theme Name');

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(1);
        $searchResult->method('first')->willReturn($themeEntity);

        $this->themeRepository->method('search')->willReturn($searchResult);

        $this->pluginRegistry->method('getConfigurations')->willReturn(
            new StorefrontPluginConfigurationCollection([
                new StorefrontPluginConfiguration('technical-name'),
            ])
        );

        $this->themeFileResolver->method('resolveFiles')->willReturn(['resolved' => 'files']);
        $this->themeFilesystemResolver->method('getFilesystemForStorefrontConfig')->willReturn(
            new Filesystem('')
        );

        $this->commandTester->execute([
            'theme-id' => 'theme-id',
            'domain-url' => 'http://example.com',
        ]);

        static::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testFailsWhenThemeIdIsMissing(): void
    {
        $this->commandTester->execute([
            'domain-url' => 'http://example.com',
        ]);

        static::assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        static::assertStringContainsString(
            '[ERROR] No theme found which is connected to a storefront sales channel',
            $this->commandTester->getDisplay()
        );
    }

    public function testFailsWhenNoThemeFound(): void
    {
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(0);

        $this->themeRepository->method('search')->willReturn($searchResult);

        $this->commandTester->execute([
            'theme-id' => 'invalid-theme-id',
        ]);

        static::assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        static::assertStringContainsString('No theme found which is connected to a storefront sales channel', $this->commandTester->getDisplay());
    }

    public function testFailsWhenNoDomainUrlProvided(): void
    {
        $themeEntity = new ThemeEntity();
        $themeEntity->setId('theme-id');
        $themeEntity->setTechnicalName('technical-name');
        $themeEntity->setName('Theme Name');

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(1);
        $searchResult->method('first')->willReturn($themeEntity);

        $this->themeRepository->method('search')->willReturn($searchResult);

        $this->pluginRegistry->method('getConfigurations')->willReturn(
            new StorefrontPluginConfigurationCollection([
                new StorefrontPluginConfiguration('technical-name'),
            ])
        );

        $this->themeFileResolver->method('resolveFiles')->willReturn(['resolved' => 'files']);
        $this->themeFilesystemResolver->method('getFilesystemForStorefrontConfig')->willReturn(
            $this->createMock(Filesystem::class)
        );

        $this->commandTester->execute([
            'theme-id' => 'theme-id',
        ]);

        static::assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        static::assertStringContainsString('No domain URL for theme', $this->commandTester->getDisplay());
    }
}
