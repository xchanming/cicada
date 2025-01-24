<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Processing\Mapping;

use Cicada\Core\Content\ImportExport\Processing\Mapping\CriteriaBuilder;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class CriteriaBuilderTest extends TestCase
{
    use KernelTestBehaviour;

    public function testNoAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder(static::getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        static::assertEmpty($criteria->getAssociations());
    }

    public function testAssociations(): void
    {
        $criteriaBuild = new CriteriaBuilder(static::getContainer()->get(ProductDefinition::class));

        $criteria = new Criteria();
        $config = new Config(
            [
                'name',
                'translations.name',
                'visibilities.search',
                'manufacturer.media.translations.title',
            ],
            [],
            []
        );
        $criteriaBuild->enrichCriteria($config, $criteria);

        $associations = $criteria->getAssociations();
        static::assertNotEmpty($associations);

        static::assertArrayHasKey('translations', $associations);
        static::assertArrayHasKey('visibilities', $associations);

        static::assertArrayHasKey('manufacturer', $associations);
        $manufacturerAssociations = $associations['manufacturer']->getAssociations();
        static::assertArrayHasKey('media', $manufacturerAssociations);

        static::assertInstanceOf(Criteria::class, $manufacturerAssociations['media']);
        $manufacturerMediaAssociations = $manufacturerAssociations['media']->getAssociations();
        static::assertArrayHasKey('translations', $manufacturerMediaAssociations);
        static::assertInstanceOf(Criteria::class, $manufacturerMediaAssociations['translations']);
    }
}
