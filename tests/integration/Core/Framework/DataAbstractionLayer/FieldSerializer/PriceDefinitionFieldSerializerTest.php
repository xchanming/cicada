<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CurrencyPriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Rule\LineItemCustomFieldRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PriceDefinitionFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\OrRule;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\Currency\Rule\CurrencyRule;
use Cicada\Core\System\CustomField\CustomFieldTypes;

/**
 * @internal
 */
class PriceDefinitionFieldSerializerTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('serializerProvider')]
    public function testSerializer(PriceDefinitionInterface $definition): void
    {
        $serializer = static::getContainer()->get(PriceDefinitionFieldSerializer::class);

        $encoded = $serializer->encode(
            new PriceDefinitionField('test', 'test'),
            new EntityExistence('', [], false, false, false, []),
            new KeyValuePair('test', $definition, true),
            new WriteParameterBag(static::getContainer()->get(CurrencyDefinition::class), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue())
        );

        $encoded = iterator_to_array($encoded);

        static::assertArrayHasKey('test', $encoded);
        static::assertIsString($encoded['test']);

        $decoded = $serializer->decode(
            new PriceDefinitionField('test', 'test'),
            $encoded['test']
        );

        static::assertEquals($definition, $decoded);
    }

    public static function serializerProvider(): \Generator
    {
        $rule = new AndRule([
            new OrRule([
                new CurrencyRule(CurrencyRule::OPERATOR_EQ, [Defaults::CURRENCY]),
            ]),
            new CurrencyRule(CurrencyRule::OPERATOR_EQ, [Defaults::CURRENCY]),
        ]);

        yield 'Test quantity price definition' => [
            new QuantityPriceDefinition(100, new TaxRuleCollection([new TaxRule(19, 50), new TaxRule(7, 50)]), 3),
        ];

        yield 'Test absolute price definition' => [
            new AbsolutePriceDefinition(20, $rule),
        ];

        yield 'Test percentage price definition' => [
            new PercentagePriceDefinition(-20, $rule),
        ];

        yield 'Test currency price definition' => [
            new CurrencyPriceDefinition(new PriceCollection([
                new Price(Defaults::CURRENCY, 100, 200, false),
                new Price(Uuid::randomHex(), 200, 300, true),
            ]), $rule),
        ];

        $customFieldsRule = new LineItemCustomFieldRule(
            LineItemCustomFieldRule::OPERATOR_EQ,
            ['name' => 'foobar', 'type' => CustomFieldTypes::BOOL]
        );
        $customFieldsRule->assign([
            'selectedField' => 'foo',
            'selectedFieldSet' => 'bar',
            'renderedFieldValue' => null,
        ]);

        $rule = new AndRule([
            new OrRule([
                $customFieldsRule,
            ]),
            $customFieldsRule,
        ]);

        yield 'Test percentage price definition with bool type custom field rule' => [
            new PercentagePriceDefinition(-20, $rule),
        ];
    }
}
