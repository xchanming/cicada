<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Telemetry;

use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Config\MetricConfig;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\Metric;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\Type;
use Cicada\Core\Framework\Telemetry\Metrics\Transport\TransportCollection;
use Cicada\Core\Framework\Test\Telemetry\Transport\TraceableTransport;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertInstanceOf;

/**
 * @internal
 *
 * Tests against sample Metrics that the general flow is working correctly.
 * Having this tests pass does not guarantee that ALL the metrics are working correctly.
 * Rather a sanity check that the telemetry system is working as intended.
 */
#[Package('core')]
class EventTelemetryFlowTest extends TestCase
{
    use KernelTestBehaviour;

    private TraceableTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $transportsCollection = static::getContainer()->get(TransportCollection::class);
        assertInstanceOf(TransportCollection::class, $transportsCollection);
        $transport = current(iterator_to_array($transportsCollection->getIterator()));
        static::assertInstanceOf(TraceableTransport::class, $transport);
        $this->transport = $transport;

        $this->transport->reset();
    }

    public function testCacheInvalidateMetricEmitted(): void
    {
        Feature::skipTestIfInActive('TELEMETRY_METRICS', $this);
        $this->invalidateCacheFlow();

        $metricConfig = MetricConfig::fromDefinition('cache.invalidate.count', [
            'type' => Type::COUNTER->value,
            'description' => 'Number of cache invalidations',
            'enabled' => true,
        ]);
        static::assertNotEmpty($this->transport->getEmittedMetrics());
        static::assertEquals(
            Metric::fromConfigured(new ConfiguredMetric('cache.invalidate.count', 1), $metricConfig),
            $this->transport->getEmittedMetrics()[0]
        );
    }

    public function testCacheInvalidateMetricNotEmittedIfFeatureFlagIsInactive(): void
    {
        Feature::skipTestIfActive('TELEMETRY_METRICS', $this);
        $this->invalidateCacheFlow();

        static::assertEmpty($this->transport->getEmittedMetrics());
    }

    public function testDalAssociationsCountEmitted(): void
    {
        Feature::skipTestIfInActive('TELEMETRY_METRICS', $this);

        $userRepository = static::getContainer()->get('user.repository');
        $criteria = new Criteria();
        $criteria->addAssociations(['aclRoles', 'avatarMedia']);

        $metricConfig = MetricConfig::fromDefinition('dal.associations.count', [
            'type' => Type::HISTOGRAM->value,
            'description' => 'Number of associations loaded',
            'enabled' => true,
        ]);

        // search triggers EntitySearchedEvent, event is configured via attribute
        $userRepository->search($criteria, Context::createDefaultContext())->first();
        static::assertNotEmpty($this->transport->getEmittedMetrics());
        static::assertEquals(
            Metric::fromConfigured(new ConfiguredMetric('dal.associations.count', 2), $metricConfig),
            $this->transport->getEmittedMetrics()[0]
        );
    }

    private function invalidateCacheFlow(): void
    {
        $cacheInvalidator = static::getContainer()->get(CacheInvalidator::class);
        assertInstanceOf(CacheInvalidator::class, $cacheInvalidator);
        $cacheInvalidator->invalidate(['test-tag']);
    }
}
