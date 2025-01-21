<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\PrimaryKeyResolver;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Processing\Pipe\EntityPipe;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Cicada\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class EntityPipeTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEntityPipe(): void
    {
        $entityPipe = new EntityPipe(
            static::getContainer()->get(DefinitionInstanceRegistry::class),
            static::getContainer()->get(SerializerRegistry::class),
            null,
            null,
            static::getContainer()->get(PrimaryKeyResolver::class)
        );

        $sourceEntity = ProductDefinition::ENTITY_NAME;
        $config = new Config([], ['sourceEntity' => $sourceEntity], []);
        $id = Uuid::randomHex();

        $product = (new ProductEntity())->assign([
            'id' => $id,
            'stock' => 101,
            'productNumber' => 'P101',
            'active' => true,
            'translations' => new ProductTranslationCollection([
                (new ProductTranslationEntity())->assign([
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'name' => 'test product',
                    '_uniqueIdentifier' => $id . '_' . Defaults::LANGUAGE_SYSTEM,
                ]),
            ]),
        ]);
        $product->setUniqueIdentifier($id);

        $result = iterator_to_array($entityPipe->in($config, $product->jsonSerialize()));
        static::assertInstanceOf(ProductTranslationCollection::class, $product->getTranslations());
        $first = $product->getTranslations()->first();
        static::assertInstanceOf(ProductTranslationEntity::class, $first);
        $translation = $first->getName();
        static::assertIsString($translation);

        static::assertSame($product->getId(), $result['id']);
        static::assertSame($translation, $result['translations']['DEFAULT']['name']);
        static::assertSame((string) $product->getStock(), $result['stock']);
        static::assertSame($product->getProductNumber(), $result['productNumber']);
        static::assertSame('1', $result['active']);

        $result = iterator_to_array($entityPipe->out($config, $result));

        static::assertSame($product->getId(), $result['id']);
        static::assertSame($translation, $result['translations'][Defaults::LANGUAGE_SYSTEM]['name']);
        static::assertSame($product->getStock(), $result['stock']);
        static::assertSame($product->getProductNumber(), $result['productNumber']);
        static::assertSame($product->getActive(), $result['active']);
    }
}
