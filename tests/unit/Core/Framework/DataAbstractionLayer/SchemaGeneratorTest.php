<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CronIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StateMachineStateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TaxFreeConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeBreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreeLevelField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TreePathField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VariantListingConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionDataPayloadField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\SchemaGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - Will be removed with \Cicada\Core\Framework\DataAbstractionLayer\SchemaGenerator
 */
#[Package('framework')]
#[CoversClass(SchemaGenerator::class)]
class SchemaGeneratorTest extends TestCase
{
    private StaticDefinitionInstanceRegistry $registry;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $this->registry = new StaticDefinitionInstanceRegistry(
            [
                TestEntityWithAllPossibleFieldsDefinition::class,
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );
    }

    public function testDifferentFieldTypes(): void
    {
        $definition = $this->registry->get(TestEntityWithAllPossibleFieldsDefinition::class);

        $schemaBuilder = new SchemaGenerator();

        $table = $schemaBuilder->generate($definition);

        static::assertNotEmpty($table);
    }
}

/**
 * @internal
 */
class TestEntityWithAllPossibleFieldsDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'test_entity_with_all_possible_fields';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new VersionField(),
            new CreatedByField(),
            new UpdatedByField(),
            new StateMachineStateField('state_id', 'stateId', OrderStates::STATE_MACHINE),
            new CreatedAtField(),
            new UpdatedAtField(),
            new DateTimeField('datetime', 'datetime'),
            new DateField('date', 'date'),
            new CartPriceField('cart_price', 'cartPrice'),
            new CalculatedPriceField('calculated_price', 'calculatedPrice'),
            new PriceField('price', 'price'),
            new PriceDefinitionField('price_definition', 'priceDefinition'),
            new JsonField('json', 'json'),
            new ListField('list', 'list'),
            new ConfigJsonField('config_json', 'configJson'),
            new CustomFields(),
            new BreadcrumbField(),
            new CashRoundingConfigField('cash_rounding_config', 'cashRoundingConfig'),
            new ObjectField('object', 'object'),
            new TaxFreeConfigField('tax_free_config', 'taxFreeConfig'),
            new TreeBreadcrumbField('tree_breadcrumb', 'treeBreadcrumb'),
            new VariantListingConfigField('variant_listing_config', 'variantListingConfig'),
            new VersionDataPayloadField('version_data_payload', 'versionDataPayload'),
            new ChildCountField(),
            new IntField('int', 'int'),
            new AutoIncrementField(),
            new TreeLevelField('tree_level', 'treeLevel'),
            new BoolField('bool', 'bool'),
            new LockedField(),
            new PasswordField('password', 'password'),
            new StringField('string', 'string'),
            new TimeZoneField('timezone', 'timezone'),
            new CronIntervalField('cron_interval', 'cronInterval'),
            new DateIntervalField('date_interval', 'dateInterval'),
            new EmailField('email', 'email'),
            new RemoteAddressField('remote_address', 'remoteAddress'),
            new NumberRangeField('number_range', 'numberRange'),
            new BlobField('blob', 'blob'),
            new FloatField('float', 'float'),
            new TreePathField('tree_path', 'treePath'),
            new LongTextField('long_text', 'longText'),
        ]);
    }
}
