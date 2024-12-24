<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Product;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\SearchKeywordUpdater;
use Cicada\Core\Framework\Context;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use Cicada\Elasticsearch\Product\SearchKeywordReplacement;

/**
 * @internal
 */
#[CoversClass(SearchKeywordReplacement::class)]
class SearchKeywordReplacementTest extends TestCase
{
    public function testSearchKeywordReplacement(): void
    {
        $decorated = $this->createMock(SearchKeywordUpdater::class);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper->method('allowIndexing')->willReturn(true);

        $replacement = new SearchKeywordReplacement($decorated, $helper);
        $replacement->update([], Context::createDefaultContext());
        $decorated->expects(static::never())->method('update');
    }

    public function testSearchKeywordReplacementDisabled(): void
    {
        $decorated = $this->createMock(SearchKeywordUpdater::class);

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper->method('allowIndexing')->willReturn(false);

        $replacement = new SearchKeywordReplacement($decorated, $helper);
        $decorated->expects(static::once())->method('update');
        $replacement->update([], Context::createDefaultContext());
    }

    public function testReset(): void
    {
        $decorated = $this->createMock(SearchKeywordUpdater::class);
        $decorated->expects(static::once())->method('reset');
        $replacement = new SearchKeywordReplacement($decorated, $this->createMock(ElasticsearchHelper::class));
        $replacement->reset();
    }
}
