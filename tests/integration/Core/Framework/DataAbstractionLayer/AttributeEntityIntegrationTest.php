<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeEntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeMappingDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldType\DateInterval;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture\AttributeEntity;
use Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture\AttributeEntityCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AttributeEntityIntegrationTest extends TestCase
{
    use BasicTestDataBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    protected function setUp(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->commit();

        $sql = file_get_contents(__DIR__ . '/fixture/schema.sql');
        static::assertIsString($sql);
        try {
            $connection->executeStatement($sql);
        } catch (\Exception $e) {
            // restart transaction, otherwise follow-up tests will fail
            $connection->beginTransaction();
            static::fail($e->getMessage());
        }

        $connection->beginTransaction();
    }

    public function testDefinitionsExist(): void
    {
        // registered in config/services_test.xml
        static::assertTrue(static::getContainer()->has('attribute_entity.repository'));
        static::assertTrue(static::getContainer()->has('attribute_entity.definition'));

        static::assertTrue(static::getContainer()->has('attribute_entity_agg.repository'));
        static::assertTrue(static::getContainer()->has('attribute_entity_agg.definition'));

        static::assertTrue(static::getContainer()->has('attribute_entity_currency.definition'));

        static::assertTrue(static::getContainer()->has('attribute_entity_translation.repository'));
        static::assertTrue(static::getContainer()->has('attribute_entity_translation.definition'));

        static::assertInstanceOf(AttributeEntityDefinition::class, static::getContainer()->get('attribute_entity.definition'));
        static::assertSame(AttributeEntityCollection::class, static::getContainer()->get('attribute_entity.definition')->getCollectionClass());
        static::assertInstanceOf(AttributeEntityDefinition::class, static::getContainer()->get('attribute_entity_agg.definition'));
        static::assertSame(EntityCollection::class, static::getContainer()->get('attribute_entity_agg.definition')->getCollectionClass());
        static::assertInstanceOf(AttributeMappingDefinition::class, static::getContainer()->get('attribute_entity_currency.definition'));
        static::assertInstanceOf(AttributeTranslationDefinition::class, static::getContainer()->get('attribute_entity_translation.definition'));

        static::assertInstanceOf(EntityRepository::class, static::getContainer()->get('attribute_entity.repository'));
        static::assertInstanceOf(EntityRepository::class, static::getContainer()->get('attribute_entity_currency.repository'));
        static::assertInstanceOf(EntityRepository::class, static::getContainer()->get('attribute_entity_agg.repository'));
        static::assertInstanceOf(EntityRepository::class, static::getContainer()->get('attribute_entity_translation.repository'));
    }

    public function testAssociationDefinitions(): void
    {
        $definition = static::getContainer()->get('attribute_entity_agg.definition');

        static::assertInstanceOf(AttributeEntityDefinition::class, $definition);
        static::assertNotNull($definition->getFields()->get('ownColumn'));
        static::assertNotNull($definition->getFields()->get('attributeEntity'));

        $field = $definition->getFields()->get('ownColumn');
        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame('attribute_entity_id', $field->getStorageName());
        static::assertSame('id', $field->getReferenceField());
        static::assertSame('attribute_entity', $field->getReferenceEntity());

        $field = $definition->getFields()->get('attributeEntity');
        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame('attribute_entity_id', $field->getStorageName());
        static::assertSame('id', $field->getReferenceField());
        static::assertSame('attribute_entity', $field->getReferenceEntity());
    }

    public function testCrudRoot(): void
    {
        $ids = new IdsCollection();

        $context = Context::createDefaultContext();

        $result = $this->repository('attribute_entity')->create([
            [
                'id' => $ids->create('first-key'),
                'string' => 'string',
                'transString' => 'transString',
                'differentName' => 'storageString',
            ],
        ], $context);

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));

        $written = $result->getPrimaryKeys('attribute_entity');
        static::assertContains($ids->get('first-key'), $written);

        $search = $this->repository('attribute_entity')
            ->search(new Criteria($ids->getList(['first-key'])), $context);

        static::assertCount(1, $search);
        static::assertTrue($search->has($ids->get('first-key')));

        $record = $search->get($ids->get('first-key'));

        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertSame($ids->get('first-key'), $record->id);
        static::assertSame('string', $record->string);
        static::assertSame('transString', $record->getTranslation('transString'));
        static::assertSame('storageString', $record->differentName);

        $result = $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'string' => 'string-updated',
                'transString' => 'transString-updated',
                'differentName' => 'storageString-updated',
            ],
        ], $context);

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));

        $written = $result->getPrimaryKeys('attribute_entity');
        static::assertContains($ids->get('first-key'), $written);

        $search = $this->repository('attribute_entity')
            ->search(new Criteria($ids->getList(['first-key'])), $context);

        static::assertCount(1, $search);
        static::assertTrue($search->has($ids->get('first-key')));

        $record = $search->get($ids->get('first-key'));

        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertSame($ids->get('first-key'), $record->id);
        static::assertSame('string-updated', $record->string);
        static::assertSame('transString-updated', $record->getTranslation('transString'));
        static::assertSame('storageString-updated', $record->differentName);

        $result = $this->repository('attribute_entity')->delete([
            ['id' => $ids->get('first-key')],
        ], $context);

        static::assertNotEmpty($result->getDeletedPrimaryKeys('attribute_entity'));

        $deleted = $result->getDeletedPrimaryKeys('attribute_entity');
        static::assertContains($ids->get('first-key'), $deleted);

        $search = $this->repository('attribute_entity')
            ->search(new Criteria($ids->getList(['first-key'])), $context);

        static::assertCount(0, $search);
    }

    public function testScalarValues(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'text' => 'text',
            'int' => 1,
            'float' => 1.1,
            'bool' => true,
            'datetime' => new \DateTimeImmutable('2020-01-01 15:15:15'),
            'date' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'dateInterval' => new \DateInterval('P1D'),
            'timeZone' => 'Europe/Berlin',
            'json' => ['key' => 'value'],
            'serialized' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 1, 'net' => 1, 'linked' => true],
            ],
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 1, 'net' => 1, 'linked' => true],
            ],
            'differentName' => 'string',
            'transString' => 'string',
            'transText' => 'text',
            'transInt' => 1,
            'transFloat' => 1.1,
            'transBool' => true,
            'transDatetime' => new \DateTimeImmutable('2020-01-01 15:15:15'),
            'transDate' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'transDateInterval' => new \DateInterval('P1D'),
            'transTimeZone' => 'Europe/Berlin',
            'transJson' => ['key' => 'value'],
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        $written = $result->getPrimaryKeys('attribute_entity');
        static::assertContains($ids->get('first-key'), $written);

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        static::assertCount(1, $search);
        static::assertTrue($search->has($ids->get('first-key')));

        $record = $search->get($ids->get('first-key'));

        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertSame('string', $record->string);
        static::assertSame('text', $record->text);
        static::assertSame(1, $record->int);
        static::assertSame(1.1, $record->float);
        static::assertTrue($record->bool);
        static::assertEquals(new \DateTimeImmutable('2020-01-01 15:15:15'), $record->datetime);
        static::assertEquals(new \DateTimeImmutable('2020-01-01 00:00:00'), $record->date);
        static::assertEquals(new DateInterval('P1D'), $record->dateInterval);
        static::assertSame('Europe/Berlin', $record->timeZone);
        static::assertSame(['key' => 'value'], $record->json);
        static::assertEquals(
            new PriceCollection([new Price(Defaults::CURRENCY, 1, 1, true)]),
            $record->serialized
        );
        static::assertEquals(
            new PriceCollection([new Price(Defaults::CURRENCY, 1, 1, true)]),
            $record->price
        );

        static::assertSame('string', $record->transString);
        static::assertSame('text', $record->transText);
        static::assertSame(1, $record->transInt);
        static::assertSame(1.1, $record->transFloat);
        static::assertTrue($record->transBool);
        static::assertEquals(new \DateTimeImmutable('2020-01-01 15:15:15'), $record->transDatetime);
        static::assertEquals(new \DateTimeImmutable('2020-01-01 00:00:00'), $record->transDate);
        static::assertEquals(new DateInterval('P1D'), $record->transDateInterval);
        static::assertSame('Europe/Berlin', $record->transTimeZone);
        static::assertSame(['key' => 'value'], $record->transJson);
        static::assertSame('string', $record->differentName);

        $json = $record->jsonSerialize();
        unset($json['extensions']);

        static::assertEquals([
            '_uniqueIdentifier' => $ids->get('first-key'),
            'versionId' => null,
            'translated' => $record->getTranslated(),
            'createdAt' => $record->getCreatedAt()?->format(\DateTimeInterface::RFC3339_EXTENDED),
            'updatedAt' => null,
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'text' => 'text',
            'int' => 1,
            'float' => 1.1,
            'bool' => true,
            'datetime' => $record->datetime?->format(\DateTimeInterface::RFC3339_EXTENDED),
            'autoIncrement' => 1,
            'json' => ['key' => 'value'],
            'date' => $record->date?->format(\DateTimeInterface::RFC3339_EXTENDED),
            'dateInterval' => new DateInterval('P1D'),
            'timeZone' => 'Europe/Berlin',
            'serialized' => new PriceCollection([new Price(Defaults::CURRENCY, 1, 1, true)]),
            'price' => new PriceCollection([new Price(Defaults::CURRENCY, 1, 1, true)]),
            'transString' => 'string',
            'transText' => 'text',
            'transInt' => 1,
            'transFloat' => 1.1,
            'transBool' => true,
            'transDatetime' => $record->transDatetime?->format(\DateTimeInterface::RFC3339_EXTENDED),
            'transJson' => ['key' => 'value'],
            'transDate' => $record->transDate?->format(\DateTimeInterface::RFC3339_EXTENDED),
            'transDateInterval' => new DateInterval('P1D'),
            'transTimeZone' => 'Europe/Berlin',
            'differentName' => 'string',
            'currencyId' => null,
            'stateId' => null,
            'followId' => null,
            'currency' => null,
            'follow' => null,
            'state' => null,
            'aggs' => null,
            'currencies' => null,
            'orders' => null,
            'translations' => null,
            'customFields' => null,
        ], $json);
    }

    public function testOneToOne(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'follow' => self::currency($ids->get('currency-1'), 'ABC'),
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        static::assertNotEmpty($result->getPrimaryKeys('currency'));
        static::assertContains($ids->get('currency-1'), $result->getPrimaryKeys('currency'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->follow);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('follow');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->follow);
        static::assertSame($ids->get('currency-1'), $record->follow->getId());

        // test on delete set null
        static::getContainer()->get('currency.repository')->delete([
            ['id' => $ids->get('currency-1')],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('follow');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->follow);
        static::assertNull($record->followId);
    }

    public function testOneToMany(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'aggs' => [
                ['id' => $ids->get('agg-1'), 'number' => 'agg-1'],
                ['id' => $ids->get('agg-2'), 'number' => 'agg-2'],
            ],
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity_agg'));
        static::assertContains($ids->get('agg-1'), $result->getPrimaryKeys('attribute_entity_agg'));
        static::assertContains($ids->get('agg-2'), $result->getPrimaryKeys('attribute_entity_agg'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->aggs);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('aggs');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->aggs);
        static::assertCount(2, $record->aggs);
        static::assertArrayHasKey($ids->get('agg-1'), $record->aggs);
        static::assertArrayHasKey($ids->get('agg-2'), $record->aggs);

        $this->repository('attribute_entity_agg')->delete([
            ['id' => $ids->get('agg-1')],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('aggs');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->aggs);
        static::assertCount(1, $record->aggs);
        static::assertArrayHasKey($ids->get('agg-2'), $record->aggs);

        // test cascade delete
        $deleted = $this->repository('attribute_entity')->delete([
            ['id' => $ids->get('first-key')],
        ], Context::createDefaultContext());

        static::assertContains($ids->get('first-key'), $deleted->getDeletedPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('agg-2'), $deleted->getDeletedPrimaryKeys('attribute_entity_agg'));
    }

    public function testManyToOne(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'currency' => self::currency($ids->get('currency-1'), 'ABC'),
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        static::assertNotEmpty($result->getPrimaryKeys('currency'));
        static::assertContains($ids->get('currency-1'), $result->getPrimaryKeys('currency'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->currency);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('currency');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->currency);
        static::assertSame($ids->get('currency-1'), $record->currency->getId());

        // set currencyId to null
        $result = $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'currencyId' => null,
            ],
        ], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('currency');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->currency);

        // set currencyId again
        $result = $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'currencyId' => $ids->get('currency-1'),
            ],
        ], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('currency');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->currency);
        static::assertSame($ids->get('currency-1'), $record->currency->getId());

        // test restrict delete
        $this->expectException(ForeignKeyConstraintViolationException::class);

        static::getContainer()->get('currency.repository')->delete([
            ['id' => $ids->get('currency-1')],
        ], Context::createDefaultContext());
    }

    public function testManyToMany(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'currencies' => [
                self::currency($ids->get('currency-1'), 'ABC'),
                self::currency($ids->get('currency-2'), 'DEF'),
            ],
        ];

        $result = $this->repository('attribute_entity')
            ->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        static::assertNotEmpty($result->getPrimaryKeys('currency'));
        static::assertContains($ids->get('currency-1'), $result->getPrimaryKeys('currency'));
        static::assertContains($ids->get('currency-2'), $result->getPrimaryKeys('currency'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->currencies);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('currencies');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->currencies);
        static::assertCount(2, $record->currencies);
        static::assertArrayHasKey($ids->get('currency-1'), $record->currencies);
        static::assertArrayHasKey($ids->get('currency-2'), $record->currencies);

        $this->repository('attribute_entity_currency')->delete([
            ['attributeEntityId' => $ids->get('first-key'), 'currencyId' => $ids->get('currency-1')],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('currencies');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->currencies);
        static::assertCount(1, $record->currencies);
        static::assertArrayHasKey($ids->get('currency-2'), $record->currencies);
    }

    public function testState(): void
    {
        $ids = new IdsCollection();

        $stateId = $this->getStateMachineState(OrderStates::STATE_MACHINE, OrderStates::STATE_COMPLETED);
        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'stateId' => $stateId,
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->state);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('state');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->state);
        static::assertSame($stateId, $record->state->getId());

        // set stateId to null
        $result = $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'stateId' => null,
            ],
        ], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('state');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNull($record->state);

        // set stateId again
        $result = $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'stateId' => $stateId,
            ],
        ], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('state');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertNotNull($record->state);
        static::assertSame($stateId, $record->state->getId());
    }

    public function testTranslations(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => [
                'en-GB' => 'transString',
                'de-DE' => 'transString-de',
            ],
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $context = Context::createDefaultContext();
        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), $context);

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertSame('transString', $record->getTranslation('transString'));
        // translation association was not loaded in the criteria
        static::assertEmpty($record->translations);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('translations');
        $languageContext = new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $context->getCurrencyId(),
            [$this->getDeDeLanguageId(), Defaults::LANGUAGE_SYSTEM],
        );
        $search = $this->repository('attribute_entity')
            ->search($criteria, $languageContext);

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertSame('transString', $record->getTranslation('transString'));
        $translations = $record->translations ?? [];
        static::assertCount(1, $translations);

        foreach ($translations as $translation) {
            static::assertInstanceOf(ArrayEntity::class, $translation);
            if ($translation->get('languageId') === Defaults::LANGUAGE_SYSTEM) {
                static::assertSame('transString', $translation->get('transString'));
            } else {
                static::assertSame('transString-de', $translation->get('transString'));
            }
        }
    }

    public function testCustomFields(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
        ];

        $result = $this->repository('attribute_entity')->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        $context = Context::createDefaultContext();
        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), $context);

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertEmpty($record->getCustomFields());

        $this->repository('attribute_entity')->update([
            [
                'id' => $ids->get('first-key'),
                'customFields' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
        ], Context::createDefaultContext());

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), $context);

        $record = $search->get($ids->get('first-key'));
        static::assertInstanceOf(AttributeEntity::class, $record);
        static::assertEquals([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $record->getCustomFields());
        static::assertSame('bar', $record->getCustomFieldsValue('foo'));
        static::assertSame('baz', $record->getCustomFieldsValue('bar'));
    }

    public function testManyToManyVersioned(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->get('first-key'),
            'string' => 'string',
            'transString' => 'transString',
            'orders' => [
                self::order($ids->get('order-1'), $this->getStateMachineState(), $this->getValidCountryId()),
                self::order($ids->get('order-2'), $this->getStateMachineState(), $this->getValidCountryId()),
            ],
        ];

        $result = $this->repository('attribute_entity')
            ->create([$data], Context::createDefaultContext());

        static::assertNotEmpty($result->getPrimaryKeys('attribute_entity'));
        static::assertContains($ids->get('first-key'), $result->getPrimaryKeys('attribute_entity'));

        static::assertNotEmpty($result->getPrimaryKeys('order'));
        static::assertContains($ids->get('order-1'), $result->getPrimaryKeys('order'));
        static::assertContains($ids->get('order-2'), $result->getPrimaryKeys('order'));

        $search = $this->repository('attribute_entity')
            ->search(new Criteria([$ids->get('first-key')]), Context::createDefaultContext());

        /** @var AttributeEntity $record */
        $record = $search->get($ids->get('first-key'));
        static::assertNull($record->orders);

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('orders');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        /** @var AttributeEntity $record */
        $record = $search->get($ids->get('first-key'));
        static::assertNotNull($record->orders);
        static::assertCount(2, $record->orders);
        static::assertArrayHasKey($ids->get('order-1'), $record->orders);
        static::assertArrayHasKey($ids->get('order-2'), $record->orders);

        $this->repository('attribute_entity_order')->delete([
            ['attributeEntityId' => $ids->get('first-key'), 'orderId' => $ids->get('order-1')],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$ids->get('first-key')]);
        $criteria->addAssociation('orders');
        $search = $this->repository('attribute_entity')
            ->search($criteria, Context::createDefaultContext());

        /** @var AttributeEntity $record */
        $record = $search->get($ids->get('first-key'));
        static::assertNotNull($record->orders);
        static::assertCount(1, $record->orders);
        static::assertArrayHasKey($ids->get('order-2'), $record->orders);
    }

    private function repository(string $entity): EntityRepository
    {
        $repository = static::getContainer()->get($entity . '.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    /**
     * @return array<string, mixed>
     */
    private static function currency(string $id, string $iso): array
    {
        return [
            'id' => $id,
            'name' => 'currency-' . $id,
            'factor' => 1.5,
            'symbol' => '€',
            'isoCode' => $iso,
            'shortName' => 'Euro',
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function order(string $id, string $stateId, string $countryId): array
    {
        $addressId = (new IdsCollection())->get('address-1');

        return [
            'id' => $id,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderDateTime' => '2024-05-06 12:34:56',
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $stateId,
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $addressId,
            'addresses' => [
                [
                    'name' => 'John',
                    'street' => 'Main Street',
                    'zipcode' => '59438-0403',
                    'city' => 'City',
                    'countryId' => $countryId,
                    'id' => $addressId,
                ],
            ],
        ];
    }
}
