<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\DependencyInjection;

use Cicada\Elasticsearch\DependencyInjection\Configuration;
use Cicada\Elasticsearch\DependencyInjection\ElasticsearchExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[CoversClass(ElasticsearchExtension::class)]
class ElasticsearchExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new ElasticsearchExtension();
        $container = new ContainerBuilder();
        $extension->load([
            'elasticsearch' => [
                'hosts' => 'localhost',
            ],
        ], $container);

        $parameters = $container->getParameterBag()->all();

        static::assertArrayHasKey('elasticsearch.hosts', $parameters);
        static::assertSame('localhost', $parameters['elasticsearch.hosts']);
    }

    public function testGetConfiguration(): void
    {
        $extension = new ElasticsearchExtension();
        $configuration = $extension->getConfiguration([], new ContainerBuilder());
        static::assertInstanceOf(Configuration::class, $configuration);
    }
}
