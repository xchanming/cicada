<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Administration\Framework\Search\CriteriaCollection;
use Cicada\Administration\Service\AdminSearcher;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('administration')]
#[CoversClass(AdminSearcher::class)]
class AdminSearcherTest extends TestCase
{
    private MockObject&DefinitionInstanceRegistry $definitionInstanceRegistry;

    private AdminApiSource $source;

    protected function setUp(): void
    {
        $this->definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $this->source = new AdminApiSource('test');
        $this->source->setIsAdmin(false);
    }

    public function testAdminSearcherSearchWithEmptyCollection(): void
    {
        $adminSearcher = new AdminSearcher($this->definitionInstanceRegistry);

        $entities = new CriteriaCollection();

        static::assertSame([], $adminSearcher->search($entities, Context::createDefaultContext()));
    }

    public function testAdminSearcherSearchWithCriteriaNotInRegistry(): void
    {
        $this->definitionInstanceRegistry->expects(static::any())->method('has')->willReturn(false);
        $this->definitionInstanceRegistry->expects(static::never())->method('getRepository');

        $adminSearcher = new AdminSearcher($this->definitionInstanceRegistry);
        $queries = new CriteriaCollection(['product' => new Criteria()]);

        static::assertSame([], $adminSearcher->search($queries, Context::createDefaultContext($this->source)));
    }

    public function testAdminSearcherSearchWithNoReadAcl(): void
    {
        $this->definitionInstanceRegistry->expects(static::any())->method('has')->willReturn(true);
        $this->definitionInstanceRegistry->expects(static::never())->method('getRepository');

        $adminSearcher = new AdminSearcher($this->definitionInstanceRegistry);

        $queries = new CriteriaCollection(['product' => new Criteria()]);

        static::assertSame([], $adminSearcher->search($queries, Context::createDefaultContext($this->source)));
    }

    public function testAdminSearcherSearchWithReadAcl(): void
    {
        $this->definitionInstanceRegistry->expects(static::any())->method('has')->willReturn(true);
        $this->definitionInstanceRegistry->expects(static::once())->method('getRepository')->willReturn(
            $this->createMock(EntityRepository::class)
        );

        $this->source->setIsAdmin(true);

        $adminSearcher = new AdminSearcher($this->definitionInstanceRegistry);

        $queries = new CriteriaCollection(['product' => new Criteria()]);

        $result = $adminSearcher->search($queries, Context::createDefaultContext($this->source));

        static::assertCount(1, $result);
        static::assertArrayHasKey('product', $result);

        $productResult = $result['product'];
        static::assertArrayHasKey('data', $productResult);
        static::assertArrayHasKey('total', $productResult);
        static::assertEquals(0, $productResult['total']);
    }
}
