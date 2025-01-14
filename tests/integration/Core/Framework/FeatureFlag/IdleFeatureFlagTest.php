<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\FeatureFlag;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
class IdleFeatureFlagTest extends TestCase
{
    use KernelTestBehaviour;

    final public const EXCLUDED_DIRS = [
        'Docs',
        'Core/Framework/Test/FeatureFlag',
        'Administration/Resources/app/administration/node_modules',
        'Administration/Resources/app/administration/test/e2e/node_modules',
        'Storefront/Resources/app/storefront/node_modules',
        'Storefront/Resources/app/storefront/test/e2e/node_modules',
    ];

    final public const EXCLUDE_BY_FLAG = [
        'FEATURE_NEXT_1',
        'FEATURE_NEXT_2',
        'FEATURE_NEXT_101',
        'FEATURE_NEXT_102',
        'FEATURE_NEXT_123',
        'FEATURE_NEXT_1234',
        'FEATURE_NEXT_1235',
        'FEATURE_NEXT_0000', /** @see \Cicada\Tests\Unit\Core\Framework\FeatureTest */
    ];

    private static string $featureAllValue;

    public static function setUpBeforeClass(): void
    {
        self::$featureAllValue = $_SERVER['FEATURE_ALL'] ?? 'false';
    }

    public static function tearDownAfterClass(): void
    {
        $_SERVER['FEATURE_ALL'] = self::$featureAllValue;
        $_ENV['FEATURE_ALL'] = self::$featureAllValue;
    }

    protected function setUp(): void
    {
        $_SERVER['FEATURE_ALL'] = 'false';
        $_ENV['FEATURE_ALL'] = 'false';

        Feature::resetRegisteredFeatures();
        Feature::registerFeatures(self::getContainer()->getParameter('cicada.feature.flags'));
    }

    public function testNoIdleFeatureFlagsArePresent(): void
    {
        // init FeatureConfig
        $registeredFlags = array_keys(Feature::getAll());
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $sourceDirectory = $projectDir . '/src';
        $testDirectory = $projectDir . '/tests';

        // Find the right files to check
        $finder = new Finder();
        $finder->files()
            ->in($sourceDirectory)
            ->in($testDirectory)
            ->exclude(self::EXCLUDED_DIRS);

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $regex = '/FEATURE_NEXT_[0-9]+/';
            preg_match_all($regex, $contents, $keys);
            $availableFlag = array_unique($keys[0]);

            if (!empty($availableFlag)) {
                foreach ($availableFlag as $flag) {
                    if (\in_array($flag, self::EXCLUDE_BY_FLAG, true)) {
                        continue;
                    }

                    static::assertContains(
                        $flag,
                        $registeredFlags,
                        \sprintf('Found idle feature flag in: %s', $file->getPathname())
                    );
                }
            }
        }
    }
}
