<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Acl;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Acl\AclCriteriaValidator;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class AclCriteriaValidatorTest extends TestCase
{
    use KernelTestBehaviour;

    private AclCriteriaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = static::getContainer()->get(AclCriteriaValidator::class);
    }

    /**
     * @param array<int, string> $privileges
     */
    #[DataProvider('criteriaProvider')]
    public function testValidateCriteria(array $privileges, Criteria $criteria, bool $pass): void
    {
        $source = new AdminApiSource(null, null);
        $source->setPermissions($privileges);

        $context = new Context(
            $source,
            [],
            Defaults::CURRENCY,
            [Defaults::LANGUAGE_SYSTEM]
        );

        $missing = $this->validator->validate(ProductDefinition::ENTITY_NAME, $criteria, $context);

        if ($pass) {
            static::assertEmpty($missing);

            return;
        }

        static::assertNotEmpty($missing);
    }

    /**
     * @return array<string, array<int, array<int, string>|bool|Criteria>>
     */
    public static function criteriaProvider(): array
    {
        return [
            // association validation
            'Has read permission for root entity' => [
                ['product:read'],
                new Criteria(),
                true,
            ],
            'Missing permissions for root entity' => [
                [],
                new Criteria(),
                false,
            ],
            'Has permissions for association' => [
                ['product:read', 'product_manufacturer:read'],
                (new Criteria())->addAssociation('manufacturer'),
                true,
            ],
            'Missing permissions for association' => [
                ['product:read'],
                (new Criteria())->addAssociation('manufacturer'),
                false,
            ],
            'Has permissions for association but not for root' => [
                ['product_manufacturer:read'],
                (new Criteria())->addAssociation('manufacturer'),
                false,
            ],
            'Has permissions for nested association' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())->addAssociation('categories.media'),
                true,
            ],
            'Missing permissions for nested association' => [
                ['product:read', 'category:read'],
                (new Criteria())->addAssociation('categories.media'),
                false,
            ],

            // filter field validation
            'Has permissions for filter' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addFilter(new EqualsFilter('categories.active', true)),
                true,
            ],
            'Missing permissions for filter' => [
                ['product:read'],
                (new Criteria())
                    ->addFilter(new EqualsFilter('categories.active', true)),
                false,
            ],
            'Has permissions for nested filter' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addFilter(new EqualsFilter('categories.media.private', true)),
                true,
            ],
            'Missing permissions for nested filter' => [
                ['product:read'],
                (new Criteria())
                    ->addFilter(new EqualsFilter('categories.media.private', true)),
                false,
            ],

            // post filter validation
            'Has permissions for post filter' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addPostFilter(new EqualsFilter('categories.active', true)),
                true,
            ],
            'Missing permissions for post filter' => [
                ['product:read'],
                (new Criteria())
                    ->addPostFilter(new EqualsFilter('categories.active', true)),
                false,
            ],
            'Has permissions for nested post filter' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addPostFilter(new EqualsFilter('categories.media.private', true)),
                true,
            ],
            'Missing permissions for nested post filter' => [
                ['product:read'],
                (new Criteria())
                    ->addPostFilter(new EqualsFilter('categories.media.private', true)),
                false,
            ],

            // sorting validation
            'Has permissions for sorting' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addSorting(new FieldSorting('categories.active')),
                true,
            ],
            'Missing permissions for sorting' => [
                ['product:read'],
                (new Criteria())
                    ->addSorting(new FieldSorting('categories.active')),
                false,
            ],
            'Has permissions for nested sorting' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addSorting(new FieldSorting('categories.media.private')),
                true,
            ],
            'Missing permissions for nested sorting' => [
                ['product:read'],
                (new Criteria())
                    ->addSorting(new FieldSorting('categories.media.private')),
                false,
            ],

            // query validation
            'Has permissions for query' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addQuery(new ScoreQuery(new EqualsFilter('categories.active', true), 100)),
                true,
            ],
            'Missing permissions for query' => [
                ['product:read'],
                (new Criteria())
                    ->addQuery(new ScoreQuery(new EqualsFilter('categories.active', true), 100)),
                false,
            ],
            'Has permissions for nested query' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addQuery(new ScoreQuery(new EqualsFilter('categories.media.private', true), 100)),
                true,
            ],
            'Missing permissions for nested query' => [
                ['product:read'],
                (new Criteria())
                    ->addQuery(new ScoreQuery(new EqualsFilter('categories.media.private', true), 100)),
                false,
            ],

            // grouping validation
            'Has permissions for grouping' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addGroupField(new FieldGrouping('categories.active')),
                true,
            ],
            'Missing permissions for grouping' => [
                ['product:read'],
                (new Criteria())
                    ->addGroupField(new FieldGrouping('categories.active')),
                false,
            ],
            'Has permissions for nested grouping' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addGroupField(new FieldGrouping('categories.media.private')),
                true,
            ],
            'Missing permissions for nested grouping' => [
                ['product:read'],
                (new Criteria())
                    ->addGroupField(new FieldGrouping('categories.media.private')),
                false,
            ],

            // aggregation validation
            'Has permissions for aggregation' => [
                ['product:read', 'category:read'],
                (new Criteria())
                    ->addAggregation(new CountAggregation('count-agg', 'categories.active')),
                true,
            ],
            'Missing permissions for aggregation' => [
                ['product:read'],
                (new Criteria())
                    ->addAggregation(new CountAggregation('count-agg', 'categories.active')),
                false,
            ],
            'Has permissions for nested aggregation' => [
                ['product:read', 'category:read', 'media:read'],
                (new Criteria())
                    ->addAggregation(new CountAggregation('count-agg', 'categories.media.private')),
                true,
            ],
            'Missing permissions for nested aggregation' => [
                ['product:read'],
                (new Criteria())
                    ->addAggregation(new CountAggregation('count-agg', 'categories.media.private')),
                false,
            ],
        ];
    }
}
