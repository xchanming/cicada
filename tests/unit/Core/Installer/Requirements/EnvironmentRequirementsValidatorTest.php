<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer\Requirements;

use Composer\Composer;
use Composer\Package\Link;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Installer\Requirements\EnvironmentRequirementsValidator;
use Cicada\Core\Installer\Requirements\Struct\RequirementCheck;
use Cicada\Core\Installer\Requirements\Struct\RequirementsCheckCollection;
use Cicada\Core\Installer\Requirements\Struct\SystemCheck;

/**
 * @internal
 */
#[CoversClass(EnvironmentRequirementsValidator::class)]
class EnvironmentRequirementsValidatorTest extends TestCase
{
    /**
     * @param array<string, string> $composerOverrides
     * @param array<string, Link> $requires
     * @param SystemCheck[] $expectedChecks
     */
    #[DataProvider('composerRequirementsProvider')]
    public function testValidateRequirements(?string $coreComposerName, array $composerOverrides, array $requires, array $expectedChecks): void
    {
        $systemEnvironment = new PlatformRepository([], $composerOverrides);

        $corePackage = new RootPackage($coreComposerName ?? 'cicada-ag/platform', '1.0.0', '1.0.0');
        $corePackage->setRequires($requires);

        $repoManagerMock = $this->createMock(RepositoryManager::class);

        if ($coreComposerName) {
            $repoManagerMock->method('getLocalRepository')->willReturn(
                new InstalledArrayRepository([$corePackage])
            );
        } else {
            $repoManagerMock->method('getLocalRepository')->willReturn(new InstalledArrayRepository());
        }

        $composer = $this->createMock(Composer::class);
        $composer->method('getRepositoryManager')->willReturn($repoManagerMock);

        if ($coreComposerName) {
            $composer->expects(static::never())->method('getPackage');
        } else {
            $composer->expects(static::once())->method('getPackage')->willReturn($corePackage);
        }

        $validator = new EnvironmentRequirementsValidator($composer, $systemEnvironment);

        $checks = new RequirementsCheckCollection();

        static::assertEquals($expectedChecks, $validator->validateRequirements($checks)->getElements());
    }

    public static function composerRequirementsProvider(): \Generator
    {
        $versionParser = new VersionParser();

        yield 'platform repo with satisfied requirement' => [
            'cicada-ag/platform',
            [
                'php' => '7.4.3',
            ],
            [
                'someRequirement' => new Link(
                    'cicada-ag/platform',
                    'someRequirement',
                    $versionParser->parseConstraints('>=1.3.0'),
                    Link::TYPE_REQUIRE
                ),
                'php' => new Link(
                    'cicada-ag/platform',
                    'php',
                    $versionParser->parseConstraints('>=7.4.3'),
                    Link::TYPE_REQUIRE
                ),
            ],
            [
                new SystemCheck(
                    'php',
                    RequirementCheck::STATUS_SUCCESS,
                    '>=7.4.3',
                    '7.4.3'
                ),
            ],
        ];

        yield 'platform repo with not satisfied requirement' => [
            'cicada-ag/platform',
            [
                'php' => '7.4.2',
            ],
            [
                'someRequirement' => new Link(
                    'cicada-ag/platform',
                    'someRequirement',
                    $versionParser->parseConstraints('>=1.3.0'),
                    Link::TYPE_REQUIRE
                ),
                'php' => new Link(
                    'cicada-ag/platform',
                    'php',
                    $versionParser->parseConstraints('>=7.4.3'),
                    Link::TYPE_REQUIRE
                ),
            ],
            [
                new SystemCheck(
                    'php',
                    RequirementCheck::STATUS_ERROR,
                    '>=7.4.3',
                    '7.4.2'
                ),
            ],
        ];

        yield 'platform repo with missing requirement' => [
            'cicada-ag/platform',
            [
                'composer-runtime-api' => false,
            ],
            [
                'someRequirement' => new Link(
                    'cicada-ag/platform',
                    'someRequirement',
                    $versionParser->parseConstraints('>=1.3.0'),
                    Link::TYPE_REQUIRE
                ),
                'composer-runtime-api' => new Link(
                    'cicada-ag/platform',
                    'composer-runtime-api',
                    $versionParser->parseConstraints('^2.0'),
                    Link::TYPE_REQUIRE
                ),
            ],
            [
                new SystemCheck(
                    'composer-runtime-api',
                    RequirementCheck::STATUS_ERROR,
                    '^2.0',
                    '-'
                ),
            ],
        ];

        yield 'core repo with satisfied requirement' => [
            'cicada-ag/core',
            [
                'php' => '7.4.3',
            ],
            [
                'someRequirement' => new Link(
                    'cicada-ag/core',
                    'someRequirement',
                    $versionParser->parseConstraints('>=1.3.0'),
                    Link::TYPE_REQUIRE
                ),
                'php' => new Link(
                    'cicada-ag/core',
                    'php',
                    $versionParser->parseConstraints('>=7.4.3'),
                    Link::TYPE_REQUIRE
                ),
            ],
            [
                new SystemCheck(
                    'php',
                    RequirementCheck::STATUS_SUCCESS,
                    '>=7.4.3',
                    '7.4.3'
                ),
            ],
        ];

        yield 'fallback package with satisfied requirement' => [
            null,
            [
                'php' => '7.4.3',
            ],
            [
                'someRequirement' => new Link(
                    'cicada-ag/platform',
                    'someRequirement',
                    $versionParser->parseConstraints('>=1.3.0'),
                    Link::TYPE_REQUIRE
                ),
                'php' => new Link(
                    'cicada-ag/platform',
                    'php',
                    $versionParser->parseConstraints('>=7.4.3'),
                    Link::TYPE_REQUIRE
                ),
            ],
            [
                new SystemCheck(
                    'php',
                    RequirementCheck::STATUS_SUCCESS,
                    '>=7.4.3',
                    '7.4.3'
                ),
            ],
        ];
    }
}
