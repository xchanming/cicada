<?php declare(strict_types=1);

namespace Cicada\WebInstaller\Services;

use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Semver\VersionParser;
use Composer\Util\Platform;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[Package('core')]
class ProjectComposerJsonUpdater
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function update(string $file, string $latestVersion): void
    {
        $cicadaPackages = [
            'cicada/core',
            'cicada/administration',
            'cicada/storefront',
            'cicada/elasticsearch',
        ];

        /** @var array{minimum-stability?: string, require: array<string, string>} $composerJson */
        $composerJson = json_decode((string) file_get_contents($file), true, \JSON_THROW_ON_ERROR);

        if (str_contains(strtolower($latestVersion), 'rc')) {
            $composerJson['minimum-stability'] = 'RC';
        } else {
            unset($composerJson['minimum-stability']);
        }

        // We require symfony runtime now directly in src/Core, so we remove the max version constraint
        if (isset($composerJson['require']['symfony/runtime'])) {
            $composerJson['require']['symfony/runtime'] = '>=5';
        }

        // Lock the composer version to that major version
        $version = $this->getVersion($latestVersion);

        if ($conflictPackageVersion = $this->getConflictMinVersion($latestVersion)) {
            $composerJson['require']['cicada/conflicts'] = '>=' . $conflictPackageVersion;
        }

        foreach ($cicadaPackages as $cicadaPackage) {
            if (!isset($composerJson['require'][$cicadaPackage])) {
                continue;
            }

            $composerJson['require'][$cicadaPackage] = $version;
        }

        $composerJson = $this->configureRepositories($composerJson);

        file_put_contents($file, json_encode($composerJson, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }

    private function getVersion(string $latestVersion): string
    {
        $nextVersion = Platform::getEnv('SW_RECOVERY_NEXT_VERSION');
        if (\is_string($nextVersion)) {
            $nextBranch = Platform::getEnv('SW_RECOVERY_NEXT_BRANCH');
            if ($nextBranch === false) {
                $nextBranch = 'dev-trunk';
            }

            if ($nextBranch === $nextVersion) {
                return $nextBranch;
            }

            return $nextBranch . ' as ' . $nextVersion;
        }

        return $latestVersion;
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    private function configureRepositories(array $config): array
    {
        $repoString = Platform::getEnv('SW_RECOVERY_REPOSITORY');
        if (\is_string($repoString)) {
            try {
                $repo = json_decode($repoString, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return $config;
            }

            $config['repositories']['recovery'] = $repo;
        }

        return $config;
    }

    private function getConflictMinVersion(string $cicadaVersion): ?string
    {
        /** @var array{packages: array{"cicada/conflicts": array{version: string, require: array{"cicada/core": string}}[]}} $data */
        $data = $this->httpClient->request('GET', 'https://repo.packagist.org/p2/cicada/conflicts.json')->toArray();

        $data['packages']['cicada/conflicts'] = MetadataMinifier::expand($data['packages']['cicada/conflicts']);

        $versions = $data['packages']['cicada/conflicts'];

        $parser = new VersionParser();
        $updateToVersion = $parser->parseConstraints($parser->normalize($cicadaVersion));

        foreach ($versions as $version) {
            $cicadaVersionConstraint = $version['require']['cicada/core'];

            if ($parser->parseConstraints($cicadaVersionConstraint)->matches($updateToVersion)) {
                return $version['version'];
            }
        }

        return null;
    }
}
