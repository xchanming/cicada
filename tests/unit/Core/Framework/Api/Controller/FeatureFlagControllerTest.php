<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Api\Controller\FeatureFlagController;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Feature\FeatureFlagRegistry;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(FeatureFlagController::class)]
class FeatureFlagControllerTest extends TestCase
{
    public function testEnable(): void
    {
        $featureFlagService = $this->createMock(FeatureFlagRegistry::class);
        $featureFlagService->expects(static::once())->method('enable')->with('foo');

        $cacheClearer = $this->createMock(CacheClearer::class);
        $cacheClearer->expects(static::once())->method('clear');

        $controller = new FeatureFlagController($featureFlagService, $cacheClearer);
        $controller->enable('foo');
    }

    public function testDisable(): void
    {
        $featureFlagService = $this->createMock(FeatureFlagRegistry::class);
        $featureFlagService->expects(static::once())->method('disable')->with('foo');

        $cacheClearer = $this->createMock(CacheClearer::class);
        $cacheClearer->expects(static::once())->method('clear');

        $controller = new FeatureFlagController($featureFlagService, $cacheClearer);
        $controller->disable('foo');
    }

    public function testLoad(): void
    {
        $featureFlags = [
            'FOO' => [
                'name' => 'Foo',
                'default' => true,
                'toggleable' => true,
                'active' => false,
                'major' => true,
                'description' => 'This is a test feature',
            ],
            'BAR' => [
                'name' => 'Bar',
                'default' => true,
                'toggleable' => true,
                'active' => false,
                'major' => false,
                'description' => 'This is another test feature',
            ],
            'NEWFEATURE' => [
                'name' => 'newFeature',
                'default' => true,
                'toggleable' => true,
                'major' => false,
                'description' => 'This is new test feature',
            ],
        ];

        Feature::registerFeatures($featureFlags);

        $featureFlagService = $this->createMock(FeatureFlagRegistry::class);
        $featureFlagService->expects(static::never())->method('disable')->with('foo');

        $controller = new FeatureFlagController(
            $featureFlagService,
            $this->createMock(CacheClearer::class)
        );

        $response = $controller->load();

        $expectedFeatureFlags = $featureFlags;
        $expectedFeatureFlags['NEWFEATURE']['active'] = true;

        static::assertSame($expectedFeatureFlags, json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }
}
