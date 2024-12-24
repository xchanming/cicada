<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexConfigEvent;

/**
 * @internal
 */
#[CoversClass(ElasticsearchIndexConfigEvent::class)]
class ElasticsearchIndexConfigEventTest extends TestCase
{
    public function testEvent(): void
    {
        $event = new ElasticsearchIndexConfigEvent('index', ['config' => 'value'], $this->createMock(AbstractElasticsearchDefinition::class), Context::createDefaultContext());
        static::assertSame('index', $event->getIndexName());
        static::assertSame(['config' => 'value'], $event->getConfig());

        $event->setConfig(['config' => 'value2']);

        static::assertSame(['config' => 'value2'], $event->getConfig());
    }
}
