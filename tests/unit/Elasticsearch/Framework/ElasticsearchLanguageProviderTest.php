<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Elasticsearch\Framework\ElasticsearchLanguageProvider;
use Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexerLanguageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(ElasticsearchLanguageProvider::class)]
class ElasticsearchLanguageProviderTest extends TestCase
{
    public function testGetLanguages(): void
    {
        $languageRepository = $this->createMock(EntityRepository::class);

        $languageRepository
            ->method('search')
            ->willReturnCallback(function (Criteria $criteria) {
                static::assertTrue($criteria->hasEqualsFilter('fooo'));
                $sortings = $criteria->getSorting();
                static::assertCount(1, $sortings);
                static::assertEquals('id', $sortings[0]->getField());

                return new EntitySearchResult('foo', 0, new LanguageCollection(), null, $criteria, Context::createDefaultContext());
            });

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ElasticsearchIndexerLanguageCriteriaEvent::class, function (ElasticsearchIndexerLanguageCriteriaEvent $event): void {
            $event->getCriteria()->addFilter(new EqualsFilter('fooo', null));
        });

        $provider = new ElasticsearchLanguageProvider(
            $languageRepository,
            $dispatcher
        );

        $provider->getLanguages(
            Context::createDefaultContext()
        );
    }
}
