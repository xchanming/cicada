<?php declare(strict_types=1);

namespace Cicada\Core\Profiling\FeatureFlag;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Feature\FeatureFlagRegistry;
use Cicada\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('framework')]
class FeatureFlagProfiler extends AbstractDataCollector
{
    public function __construct(
        private readonly FeatureFlagRegistry $featureFlagService
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->featureFlagService->register();

        $features = Feature::getRegisteredFeatures();

        foreach ($features as $featureKey => $feature) {
            $features[$featureKey]['name'] = Feature::normalizeName($featureKey);
            $features[$featureKey]['active'] = Feature::isActive($featureKey);
        }

        $this->data = [
            'features' => $features,
        ];
    }

    public function getName(): string
    {
        return 'feature_flag';
    }

    public static function getTemplate(): ?string
    {
        return '@Profiling/Collector/flags.html.twig';
    }

    /**
     * @return array<string, array{name?: string, default?: bool, major?: bool, description?: string, active?: bool, name?: string}>
     */
    public function getFeatures(): array
    {
        return $this->data['features'];
    }
}
