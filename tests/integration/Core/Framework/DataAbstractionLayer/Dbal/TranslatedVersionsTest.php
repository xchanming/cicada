<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Dbal;

use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\FieldResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\TranslationFieldResolver;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TranslatedVersionsTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var string[]
     */
    private array $languages = [
        'zh-CN', 'en-GB',
    ];

    /**
     * @var EntityRepository<ProductManufacturerCollection>
     */
    private EntityRepository $manufacturerRepository;

    protected function setUp(): void
    {
        $this->manufacturerRepository = static::getContainer()->get('product_manufacturer.repository');
    }

    public function testTranslationsAreAllSelectable(): void
    {
        $zhContext = Context::createDefaultContext();
        $manufacturerId = Uuid::randomHex();

        $this->createManufacturer($this->manufacturerRepository, $manufacturerId, $zhContext);

        $versionId = $this->manufacturerRepository->createVersion($manufacturerId, $zhContext);
        $zhVersionContext = $zhContext->createWithVersionId($versionId);
        $enContext = $this->createEnContext($zhContext);
        $enVersionContext = $enContext->createWithVersionId($versionId);

        $this->manufacturerRepository->update([[
            'id' => $manufacturerId,
            'name' => 'version-zh-CN',
        ]], $zhVersionContext);

        $this->manufacturerRepository->update([[
            'id' => $manufacturerId,
            'name' => 'version-en-GB',
        ]], $enVersionContext);

        $enOriginal = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $zhContext)->getEntities()->first();
        $enVersion = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $zhVersionContext)->getEntities()->first();
        $deOriginal = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $enContext)->getEntities()->first();
        $deVersion = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $enVersionContext)->getEntities()->first();

        static::assertNotNull($enOriginal);
        static::assertNotNull($enVersion);
        static::assertNotNull($deOriginal);
        static::assertNotNull($deVersion);
        static::assertSame('original-zh-CN', $enOriginal->getName());
        static::assertSame('version-zh-CN', $enVersion->getName());
        static::assertSame('original-en-GB', $deOriginal->getName());
        static::assertSame('version-en-GB', $deVersion->getName());
    }

    public function testTranslationsFallbackToOriginal(): void
    {
        $zhContext = Context::createDefaultContext();
        $manufacturerId = Uuid::randomHex();

        $this->createManufacturer($this->manufacturerRepository, $manufacturerId, $zhContext);

        $versionId = $this->manufacturerRepository->createVersion($manufacturerId, $zhContext);
        $zhVersionContext = $zhContext->createWithVersionId($versionId);
        $enContext = $this->createEnContext($zhContext);
        $enVersionContext = $enContext->createWithVersionId($versionId);

        $this->manufacturerRepository->update([[
            'id' => $manufacturerId,
            'name' => 'version-zh-CN',
        ]], $zhVersionContext);

        $enOriginal = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $zhContext)->getEntities()->first();
        $enVersion = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $zhVersionContext)->getEntities()->first();
        $deOriginal = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $enContext)->getEntities()->first();
        $deVersion = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $enVersionContext)->getEntities()->first();

        static::assertNotNull($enOriginal);
        static::assertNotNull($enVersion);
        static::assertNotNull($deOriginal);
        static::assertNotNull($deVersion);
        static::assertSame('original-zh-CN', $enOriginal->getName());
        static::assertSame('version-zh-CN', $enVersion->getName());
        static::assertSame('original-en-GB', $deOriginal->getName());
        static::assertSame('original-en-GB', $deVersion->getName());
    }

    public function testInheritenceWithProductsAllAreTranslated(): void
    {
        $zhContext = Context::createDefaultContext();
        $enContext = $this->createEnContext($zhContext);
        $productRepository = static::getContainer()->get('product.repository');

        $ids = $this->createParentChildProduct();

        $zhVersionContext = $zhContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $zhContext));
        $enVersionContext = $enContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $enContext));
        $productRepository->update([['id' => $ids->get('child'), 'name' => 'child-version-zh-CN']], $zhVersionContext);
        $productRepository->update([['id' => $ids->get('child'), 'name' => 'child-version-en-GB']], $enVersionContext);

        $this->assertProductNames([
            ['child-original-zh-CN', $zhContext],
            ['child-original-en-GB', $enContext],
            ['child-version-zh-CN', $zhVersionContext],
            ['child-version-en-GB', $enVersionContext],
        ], $ids->get('child'));
        $this->assertProductNames([
            ['parent-original-zh-CN', $zhContext],
            ['parent-original-en-GB', $enContext],
            ['parent-original-zh-CN', $zhVersionContext],
            ['parent-original-en-GB', $enVersionContext],
        ], $ids->get('parent'));
    }

    public function testInheritanceWithProductsOnlyEnInVersionTranslated(): void
    {
        $zhContext = Context::createDefaultContext();
        $enContext = $this->createEnContext($zhContext);
        $productRepository = static::getContainer()->get('product.repository');

        $ids = $this->createParentChildProduct();

        $zhVersionContext = $zhContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $zhContext));
        $enVersionContext = $enContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $enContext));
        $productRepository->update([['id' => $ids->get('child'), 'name' => 'child-version-zh-CN']], $zhVersionContext);

        $this->assertProductNames([
            ['child-original-zh-CN', $zhContext],
            ['child-original-en-GB', $enContext],
            ['child-version-zh-CN', $zhVersionContext],
            ['child-original-en-GB', $enVersionContext],
        ], $ids->get('child'));
        $this->assertProductNames([
            ['parent-original-zh-CN', $zhContext],
            ['parent-original-en-GB', $enContext],
            ['parent-original-zh-CN', $zhVersionContext],
            ['parent-original-en-GB', $enVersionContext],
        ], $ids->get('parent'));
    }

    public function testInheritanceWithOnlyParentTranslations(): void
    {
        $zhContext = Context::createDefaultContext();
        $enContext = $this->createEnContext($zhContext);
        $productRepository = static::getContainer()->get('product.repository');

        $ids = $this->createParentChildProduct(false);

        $zhVersionContext = $zhContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $zhContext));
        $enVersionContext = $enContext->createWithVersionId($productRepository->createVersion($ids->get('child'), $enContext));

        $this->assertProductNames([
            ['parent-original-zh-CN', $zhContext],
            ['parent-original-en-GB', $enContext],
            ['parent-original-zh-CN', $zhVersionContext],
            ['parent-original-en-GB', $enVersionContext],
        ], $ids->get('parent'));

        $this->assertProductNames([
            ['parent-original-zh-CN', $zhContext],
            ['parent-original-en-GB', $enContext],
            ['parent-original-zh-CN', $zhVersionContext],
            ['parent-original-en-GB', $enVersionContext],
        ], $ids->get('child'));
    }

    public function testFieldResolverThrowsOnNotTranslatedEntities(): void
    {
        $resolver = static::getContainer()->get(TranslationFieldResolver::class);
        $context = new FieldResolverContext(
            '',
            '',
            new TranslatedField(''),
            static::getContainer()->get(ProductManufacturerTranslationDefinition::class),
            static::getContainer()->get(ProductManufacturerTranslationDefinition::class),
            new QueryBuilder(static::getContainer()->get(Connection::class)),
            Context::createDefaultContext(),
            null
        );

        $this->expectException(\RuntimeException::class);
        $resolver->join($context);
    }

    public function testFieldResolverReturnsOnNotTranslatedFields(): void
    {
        $resolver = static::getContainer()->get(TranslationFieldResolver::class);
        $result = $resolver->join(new FieldResolverContext(
            '',
            'THIS_SHOULD_BE_RETURNED',
            new StringField('', ''),
            static::getContainer()->get(ProductManufacturerDefinition::class),
            static::getContainer()->get(ProductManufacturerDefinition::class),
            new QueryBuilder(static::getContainer()->get(Connection::class)),
            Context::createDefaultContext(),
            null
        ));

        static::assertSame('THIS_SHOULD_BE_RETURNED', $result);
    }

    private function createManufacturer(EntityRepository $productManufacturerRepository, string $productManufacturerId, Context $context): void
    {
        $translations = $this->getTestTranslations();
        $productManufacturerRepository->create([[
            'id' => $productManufacturerId,
            'translations' => $translations,
        ]], $context);
    }

    private function createEnContext(Context $enContext): Context
    {
        $enLanguageId = $this->getEnGbLanguageId();

        return new Context(
            $enContext->getSource(),
            $enContext->getRuleIds(),
            $enContext->getCurrencyId(),
            [$enLanguageId, $enContext->getLanguageId()],
            $enContext->getVersionId(),
            $enContext->getCurrencyFactor(),
            $enContext->considerInheritance(),
            $enContext->getTaxState()
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getTestTranslations(string $prefix = ''): array
    {
        $translations = [];
        foreach ($this->languages as $locale) {
            $translations[$locale] = ['name' => $prefix . 'original-' . $locale];
        }

        return $translations;
    }

    /**
     * @param array<int, array<int, Context|string>> $assertions
     */
    private function assertProductNames(array $assertions, string $id): void
    {
        foreach ($assertions as [$name, $context]) {
            static::assertInstanceOf(Context::class, $context);
            static::assertIsString($name);
            $this->assertProductName($name, $id, $context);
        }
    }

    private function assertProductName(string $name, string $id, Context $context): void
    {
        $context->setConsiderInheritance(true);

        /** @var ProductEntity $product */
        $product = static::getContainer()
            ->get('product.repository')
            ->search(new Criteria([$id]), $context)->first();

        static::assertTrue($context->considerInheritance());
        static::assertSame($name, $product->getTranslated()['name'], \sprintf(
            'Expected %s with language chain %s but got %s, version context: %s',
            $name,
            (string) print_r($context->getLanguageIdChain(), true),
            $product->getName(),
            $context->getVersionId() === Defaults::LIVE_VERSION ? 'NO' : 'YES'
        ));

        $context->setConsiderInheritance(false);
    }

    private function createParentChildProduct(bool $addChildTranslations = true): IdsCollection
    {
        $context = Context::createDefaultContext();
        $ids = new IdsCollection();

        $parentProduct = (new ProductBuilder($ids, 'parent'))
            ->price(100)
            ->build();

        $childProduct = (new ProductBuilder($ids, 'child'))
            ->parent('parent')
            ->price(100)
            ->build();

        unset($childProduct['name'], $parentProduct['name']);

        $parentProduct['translations'] = $this->getTestTranslations('parent-');

        if ($addChildTranslations) {
            $childProduct['translations'] = $this->getTestTranslations('child-');
        }

        static::getContainer()
            ->get('product.repository')
            ->create([$parentProduct, $childProduct], $context);

        return $ids;
    }
}
