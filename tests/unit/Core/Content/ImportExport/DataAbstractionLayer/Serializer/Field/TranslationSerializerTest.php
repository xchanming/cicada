<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\EntitySerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field\FieldSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field\TranslationsSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\ImportExportException;
use Cicada\Core\Content\ImportExport\Processing\Mapping\MappingCollection;
use Cicada\Core\Content\ImportExport\Processing\Mapping\UpdateByCollection;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Locale\LocaleEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\DependencyInjection\Container;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(TranslationsSerializer::class)]
class TranslationSerializerTest extends TestCase
{
    public function testSerializationWithNullTranslations(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $config = $this->getConfig();

        $translations = \iterator_to_array($translationsSerializer->serialize($config, $this->getTranslationsAssociationField(), null));

        static::assertEmpty($translations);
    }

    public function testSerializationWithInvalidField(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $field = new BlobField('foo', 'bar');

        if (!Feature::isActive('v6.7.0.0')) {
            static::expectException(\InvalidArgumentException::class);
            static::expectExceptionMessage('Expected "associationField" to be an instance of "' . \InvalidArgumentException::class . '".');

            $translations = \iterator_to_array($translationsSerializer->serialize($this->getConfig(), $field, []));

            static::assertEmpty($translations);

            return;
        }

        static::expectException(ImportExportException::class);
        static::expectExceptionMessage('Expected "associationField" to be an instance of "Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField".');

        $translations = \iterator_to_array($translationsSerializer->serialize($this->getConfig(), $field, []));

        static::assertEmpty($translations);
    }

    public function testSerialization(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([
            new EntitySearchResult(
                'language',
                1,
                new LanguageCollection([
                    (new LanguageEntity())->assign([
                        'id' => Defaults::LANGUAGE_SYSTEM,
                        'translationCode' => (new LocaleEntity())->assign([
                            'code' => 'en-GB',
                        ]),
                    ]),
                ]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            ),
        ]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $translations = [
            Defaults::LANGUAGE_SYSTEM => [
                'name' => 'foo',
            ],
            'de-DE' => [
                'name' => 'bar',
            ],
        ];

        $translationsSerialized = \iterator_to_array($translationsSerializer->serialize($this->getConfig(), $this->getTranslationsAssociationField(), $translations));

        static::assertSame([
            'translations' => [
                'en-GB' => [
                    'name' => 'foo',
                ],
                'DEFAULT' => [
                    'name' => 'foo',
                ],
                'de-DE' => [
                    'name' => 'bar',
                ],
            ],
        ], $translationsSerialized);
    }

    public function testDeserializationWithEmptyTranslations(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $translations = $translationsSerializer->deserialize($this->getConfig(), $this->getTranslationsAssociationField(), []);

        static::assertNull($translations);
    }

    public function testDeserializationWithInvalidField(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $field = new BlobField('foo', 'bar');

        if (!Feature::isActive('v6.7.0.0')) {
            static::expectException(\InvalidArgumentException::class);
            static::expectExceptionMessage('Expected "associationField" to be an instance of "*ToOneField".');

            $translations = \iterator_to_array($translationsSerializer->serialize($this->getConfig(), $field, []));

            static::assertEmpty($translations);

            return;
        }

        static::expectException(ImportExportException::class);
        static::expectExceptionMessage('Expected "associationField" to be an instance of "*ToOneField".');

        $translations = $translationsSerializer->deserialize($this->getConfig(), $field, []);

        static::assertEmpty($translations);
    }

    public function testDeserialization(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);

        $translationsSerializer = $this->getTranslationSerializer($languageRepository);

        $translations = [
            'DEFAULT' => [
                'name' => 'foo',
            ],
            'de-DE' => [
                'name' => 'bar',
            ],
            'en-GB' => [],
        ];

        $translationsSerialized = $translationsSerializer->deserialize($this->getConfig(), $this->getTranslationsAssociationField(), $translations);

        static::assertSame([
            'de-DE' => [
                'name' => 'bar',
            ],
            Defaults::LANGUAGE_SYSTEM => [
                'name' => 'foo',
            ],
        ], $translationsSerialized);
    }

    public function testSupports(): void
    {
        /** @var StaticEntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = new StaticEntityRepository([]);
        $translationsSerializer = new TranslationsSerializer($languageRepository);

        static::assertTrue($translationsSerializer->supports($this->getTranslationsAssociationField()));
    }

    /**
     * @param StaticEntityRepository<LanguageCollection> $languageRepository
     */
    private function getTranslationSerializer(StaticEntityRepository $languageRepository): TranslationsSerializer
    {
        $translationsSerializer = new TranslationsSerializer(
            $languageRepository,
        );

        $entitySerializer = new EntitySerializer();
        $fieldSerializer = new FieldSerializer();

        $serializerRegistry = new SerializerRegistry([$entitySerializer], [$fieldSerializer]);
        $entitySerializer->setRegistry($serializerRegistry);
        $fieldSerializer->setRegistry($serializerRegistry);
        $translationsSerializer->setRegistry($serializerRegistry);

        return $translationsSerializer;
    }

    private function getConfig(): Config
    {
        return new Config(
            new MappingCollection(),
            [],
            new UpdateByCollection()
        );
    }

    private function getTranslationsAssociationField(): TranslationsAssociationField
    {
        $productTranslationDefinition = new ProductTranslationDefinition();
        $productDefinition = new ProductDefinition();

        $container = new Container();
        $container->set(ProductTranslationDefinition::class, $productTranslationDefinition);
        $container->set(ProductDefinition::class, $productDefinition);
        $productTranslationDefinition->compile(new DefinitionInstanceRegistry($container, [], []));

        $translationAssociationField = new TranslationsAssociationField(ProductTranslationDefinition::class, 'product_id');
        $translationAssociationField->compile(new DefinitionInstanceRegistry($container, [], []));

        return $translationAssociationField;
    }
}
