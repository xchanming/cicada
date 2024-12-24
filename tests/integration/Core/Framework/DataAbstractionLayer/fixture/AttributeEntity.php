<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\AutoIncrement;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ForeignKey;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ManyToMany;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ManyToOne;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\OnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\OneToMany;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\OneToOne;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Serialized;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\State;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Translations;
use Cicada\Core\Framework\DataAbstractionLayer\Entity as EntityStruct;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PriceFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldType\DateInterval;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

/**
 * @internal
 */
#[Entity('attribute_entity', since: '6.6.3.0', collectionClass: AttributeEntityCollection::class)]
class AttributeEntity extends EntityStruct
{
    use EntityCustomFieldsTrait;

    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[Field(type: FieldType::STRING)]
    public string $string;

    #[Field(type: FieldType::TEXT)]
    public ?string $text = null;

    #[Field(type: FieldType::INT)]
    public ?int $int;

    #[Field(type: FieldType::FLOAT)]
    public ?float $float;

    #[Field(type: FieldType::BOOL)]
    public ?bool $bool;

    #[Field(type: FieldType::DATETIME)]
    public ?\DateTimeImmutable $datetime = null;

    #[AutoIncrement]
    public int $autoIncrement;

    /**
     * @var array<string, mixed>|null
     */
    #[Field(type: FieldType::JSON)]
    public ?array $json = null;

    #[Field(type: FieldType::DATE)]
    public ?\DateTimeImmutable $date = null;

    #[Field(type: FieldType::DATE_INTERVAL)]
    public ?DateInterval $dateInterval = null;

    #[Field(type: FieldType::TIME_ZONE)]
    public ?string $timeZone = null;

    #[Serialized(serializer: PriceFieldSerializer::class, api: true)]
    public ?PriceCollection $serialized = null;

    #[Field(type: PriceField::class)]
    public ?PriceCollection $price = null;

    #[Required]
    #[Field(type: FieldType::STRING, translated: true)]
    public string $transString;

    #[Field(type: FieldType::TEXT, translated: true)]
    public ?string $transText = null;

    #[Field(type: FieldType::INT, translated: true)]
    public ?int $transInt;

    #[Field(type: FieldType::FLOAT, translated: true)]
    public ?float $transFloat;

    #[Field(type: FieldType::BOOL, translated: true)]
    public ?bool $transBool;

    #[Field(type: FieldType::DATETIME, translated: true)]
    public ?\DateTimeImmutable $transDatetime = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Field(type: FieldType::JSON, translated: true)]
    public ?array $transJson = null;

    #[Field(type: FieldType::DATE, translated: true)]
    public ?\DateTimeImmutable $transDate = null;

    #[Field(type: FieldType::DATE_INTERVAL, translated: true)]
    public ?DateInterval $transDateInterval = null;

    #[Field(type: FieldType::TIME_ZONE, translated: true)]
    public ?string $transTimeZone = null;

    #[Field(type: FieldType::STRING, translated: true, column: 'another_column_name')]
    public ?string $differentName = null;

    #[ForeignKey(entity: 'currency')]
    public ?string $currencyId = null;

    #[State(machine: OrderStates::STATE_MACHINE)]
    public ?string $stateId = null;

    #[ForeignKey(entity: 'currency')]
    public ?string $followId = null;

    #[ManyToOne(entity: 'currency', onDelete: OnDelete::RESTRICT)]
    public ?CurrencyEntity $currency = null;

    #[OneToOne(entity: 'currency', onDelete: OnDelete::SET_NULL)]
    public ?CurrencyEntity $follow = null;

    #[ManyToOne(entity: 'state_machine_state')]
    public ?StateMachineStateEntity $state = null;

    /**
     * @var array<string, AttributeEntityAgg>|null
     */
    #[OneToMany(entity: 'attribute_entity_agg', ref: 'attribute_entity_id', onDelete: OnDelete::CASCADE)]
    public ?array $aggs = null;

    /**
     * @var array<string, CurrencyEntity>|null
     */
    #[ManyToMany(entity: 'currency', onDelete: OnDelete::CASCADE)]
    public ?array $currencies = null;

    /**
     * @var array<string, OrderEntity>
     */
    #[ManyToMany(entity: 'order', onDelete: OnDelete::CASCADE)]
    public ?array $orders = null;

    /**
     * @var array<string, ArrayEntity>|null
     */
    #[Translations]
    public ?array $translations = null;
}
