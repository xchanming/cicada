<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\DependencyInjection;

use Cicada\Elasticsearch\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    public function testConfigTree(): void
    {
        $configuration = new Configuration();
        $tree = $configuration->getConfigTreeBuilder();

        static::assertSame('elasticsearch', $tree->buildTree()->getName());
    }
}
