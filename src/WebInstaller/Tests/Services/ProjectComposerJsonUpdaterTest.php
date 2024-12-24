<?php declare(strict_types=1);

namespace Cicada\WebInstaller\Tests\Services;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\WebInstaller\Services\ProjectComposerJsonUpdater;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[CoversClass(ProjectComposerJsonUpdater::class)]
#[BackupGlobals(true)]
class ProjectComposerJsonUpdaterTest extends TestCase
{
    private string $json;

    protected function setUp(): void
    {
        $this->json = __DIR__ . '/composer.json';

        file_put_contents($this->json, json_encode([
            'require' => [
                'cicada/core' => '1.2.3',
            ],
        ], \JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        unlink($this->json);
    }

    public function testUpdate(): void
    {
        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0'
        );

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.4.18.0',
                ],
            ],
            $composerJson
        );
    }

    public function testUpdateWithRC(): void
    {
        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0-rc1'
        );

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.4.18.0-rc1',
                ],
                'minimum-stability' => 'RC',
            ],
            $composerJson
        );
    }

    public function testUpdateWithFixVersion(): void
    {
        $_SERVER['SW_RECOVERY_NEXT_VERSION'] = '6.5.0.0';

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0-rc1'
        );

        unset($_SERVER['SW_RECOVERY_NEXT_VERSION']);

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => 'dev-trunk as 6.5.0.0',
                ],
                'minimum-stability' => 'RC',
            ],
            $composerJson
        );
    }

    public function testUpdateWithFixVersionAndBranch(): void
    {
        $_SERVER['SW_RECOVERY_NEXT_VERSION'] = '6.5.0.0';
        $_SERVER['SW_RECOVERY_NEXT_BRANCH'] = 'main';

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0-rc1'
        );

        unset($_SERVER['SW_RECOVERY_NEXT_VERSION']);

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => 'main as 6.5.0.0',
                ],
                'minimum-stability' => 'RC',
            ],
            $composerJson
        );
    }

    public function testUpdateWithFixVersionAndBranchSame(): void
    {
        $_SERVER['SW_RECOVERY_NEXT_VERSION'] = '6.5.0.0';
        $_SERVER['SW_RECOVERY_NEXT_BRANCH'] = '6.5.0.0';

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0-rc1'
        );

        unset($_SERVER['SW_RECOVERY_NEXT_VERSION']);

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.5.0.0',
                ],
                'minimum-stability' => 'RC',
            ],
            $composerJson
        );
    }

    public function testUpdateWithSymfonyRuntimeRequirement(): void
    {
        file_put_contents($this->json, json_encode([
            'require' => [
                'cicada/core' => '1.2.3',
                'symfony/runtime' => '^5.0|^6.0',
            ],
        ], \JSON_THROW_ON_ERROR));

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.6.0.0'
        );

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.6.0.0',
                    'symfony/runtime' => '>=5',
                ],
            ],
            $composerJson
        );
    }

    public function testUpdateConflictPackageGetsAdded(): void
    {
        file_put_contents($this->json, json_encode([
            'require' => [
                'cicada/core' => '1.2.3',
                'symfony/runtime' => '^5.0|^6.0',
            ],
        ], \JSON_THROW_ON_ERROR));

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getVersionResponse()])))->update(
            $this->json,
            '6.6.0.0'
        );

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.6.0.0',
                    'symfony/runtime' => '>=5',
                    'cicada/conflicts' => '>=2.0.0',
                ],
            ],
            $composerJson
        );
    }

    public function testUpdateConflictPackageGetsAddedUsesOlderVersionWhenConstraintDoesNotMatch(): void
    {
        file_put_contents($this->json, json_encode([
            'require' => [
                'cicada/core' => '1.2.3',
                'symfony/runtime' => '^5.0|^6.0',
            ],
        ], \JSON_THROW_ON_ERROR));

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getVersionResponse()])))->update(
            $this->json,
            '6.4.0.0'
        );

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.4.0.0',
                    'symfony/runtime' => '>=5',
                    'cicada/conflicts' => '>=1.0.0',
                ],
            ],
            $composerJson
        );
    }

    public function testWithRecoveryRepository(): void
    {
        $_SERVER['SW_RECOVERY_NEXT_VERSION'] = '6.5.0.0';
        $_SERVER['SW_RECOVERY_NEXT_BRANCH'] = '6.5.0.0';

        $customRepo = [
            'type' => 'path',
            'url' => '/my/custom/repo',
            'options' => [
                'symlink' => true,
            ],
        ];
        $_SERVER['SW_RECOVERY_REPOSITORY'] = json_encode($customRepo);

        (new ProjectComposerJsonUpdater(new MockHttpClient([$this->getEmptyVersionsResponse()])))->update(
            $this->json,
            '6.4.18.0-rc1'
        );

        unset($_SERVER['SW_RECOVERY_NEXT_VERSION']);

        $composerJson = json_decode((string) file_get_contents($this->json), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(
            [
                'require' => [
                    'cicada/core' => '6.5.0.0',
                ],
                'minimum-stability' => 'RC',
                'repositories' => [
                    'recovery' => $customRepo,
                ],
            ],
            $composerJson
        );
    }

    private function getEmptyVersionsResponse(): MockResponse
    {
        $json = <<<JSON
{
    "packages": {
        "cicada/conflicts": [
        ]
    }
}
JSON;

        return new MockResponse($json);
    }

    private function getVersionResponse(): MockResponse
    {
        $json = <<<JSON
{
    "packages": {
        "cicada/conflicts": [
          {
            "version": "2.0.0",
            "require": {
                "cicada/core": ">=6.6.0"
            }
          },
          {
            "version": "1.9.0"
          },
          {
            "version": "1.0.0",
            "require": {
                "cicada/core": "*"
            }
          }
        ]
    }
}
JSON;

        return new MockResponse($json);
    }
}
