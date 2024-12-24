<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexCreatedEvent;

/**
 * @internal
 */
#[CoversClass(ElasticsearchIndexCreatedEvent::class)]
class ElasticsearchIndexCreatedEventTest extends TestCase
{
    public function testEvent(): void
    {
        $event = new ElasticsearchIndexCreatedEvent('index', $this->createMock(AbstractElasticsearchDefinition::class));
        static::assertSame('index', $event->getIndexName());
    }
}
