<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionTranslation\PromotionTranslationDefinition;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationDefinition;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupTranslation\PropertyGroupTranslationDefinition;
use Cicada\Core\Content\Property\PropertyGroupDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldVisibility;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateTranslationDefinition;
use Cicada\Core\System\StateMachine\StateMachineDefinition;
use Cicada\Core\System\StateMachine\StateMachineTranslationDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EntityDefinitionTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEntityDefinitionCompilation(): void
    {
        $definition = static::getContainer()->get(ProductDefinition::class);

        static::assertContainsOnlyInstancesOf(Field::class, $definition->getFields());
        $productManufacturerVersionIdField = $definition->getFields()->get('productManufacturerVersionId');
        static::assertInstanceOf(ReferenceVersionField::class, $productManufacturerVersionIdField);
        static::assertSame('product_manufacturer_version_id', $productManufacturerVersionIdField->getStorageName());
        static::assertInstanceOf(ProductManufacturerDefinition::class, $productManufacturerVersionIdField->getVersionReferenceDefinition());
        static::assertSame(static::getContainer()->get(ProductManufacturerDefinition::class), $productManufacturerVersionIdField->getVersionReferenceDefinition());
    }

    public function testTranslationCompilation(): void
    {
        $definition = static::getContainer()->get(ProductTranslationDefinition::class);

        static::assertContainsOnlyInstancesOf(Field::class, $definition->getFields());
        $languageIdField = $definition->getFields()->get('languageId');
        static::assertInstanceOf(FkField::class, $languageIdField);
        static::assertSame('language_id', $languageIdField->getStorageName());
    }

    #[DataProvider('provideTranslatedDefinitions')]
    public function testTranslationsOnDefinitions(string $baseDefinitionClass, string $translationDefinitionClass): void
    {
        $baseDefinition = static::getContainer()->get($baseDefinitionClass);
        $translationDefinition = static::getContainer()->get($translationDefinitionClass);

        static::assertInstanceOf(EntityDefinition::class, $baseDefinition);
        static::assertInstanceOf(EntityTranslationDefinition::class, $translationDefinition);
        static::assertSame($translationDefinition, $baseDefinition->getTranslationDefinition());
        static::assertInstanceOf(JsonField::class, $baseDefinition->getFields()->get('translated'));
        static::assertSame($baseDefinition->getClass(), $translationDefinition->getParentDefinition()->getClass());
        static::assertSame($baseDefinition, $translationDefinition->getParentDefinition());
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function provideTranslatedDefinitions(): array
    {
        return [
            [CmsPageDefinition::class, CmsPageTranslationDefinition::class],
            [CmsSlotDefinition::class, CmsSlotTranslationDefinition::class],
            [PropertyGroupDefinition::class, PropertyGroupTranslationDefinition::class],
            [StateMachineDefinition::class, StateMachineTranslationDefinition::class],
            [StateMachineStateDefinition::class, StateMachineStateTranslationDefinition::class],
            [ProductDefinition::class, ProductTranslationDefinition::class],
            [PromotionDefinition::class, PromotionTranslationDefinition::class],
        ];
    }

    public function testGetFieldVisibility(): void
    {
        $definition = static::getContainer()->get(CustomerDefinition::class);

        $internalFields = [
            'password',
            'newsletterSalesChannelIds',
            'legacyPassword',
            'legacyEncoder',
        ];

        foreach ($internalFields as $field) {
            static::assertTrue($definition->getFieldVisibility()->isVisible($field));
            FieldVisibility::$isInTwigRenderingContext = true;
            static::assertFalse($definition->getFieldVisibility()->isVisible($field));
            FieldVisibility::$isInTwigRenderingContext = false;
        }
    }
}
