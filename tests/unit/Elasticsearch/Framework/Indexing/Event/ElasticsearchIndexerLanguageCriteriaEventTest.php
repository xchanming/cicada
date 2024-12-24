<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework\Indexing\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexerLanguageCriteriaEvent;

/**
 * @internal
 */
#[CoversClass(ElasticsearchIndexerLanguageCriteriaEvent::class)]
class ElasticsearchIndexerLanguageCriteriaEventTest extends TestCase
{
    public function testEvent(): void
    {
        $criteria = new Criteria();
        $context = Context::createDefaultContext();

        $event = new ElasticsearchIndexerLanguageCriteriaEvent($criteria, $context);
        static::assertSame($criteria, $event->getCriteria());
        static::assertSame($context, $event->getContext());
    }
}
