<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefundPosition;

use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class OrderTransactionCaptureRefundPositionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'order_transaction_capture_refund_position';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.4.12.0';
    }

    public function getEntityClass(): string
    {
        return OrderTransactionCaptureRefundPositionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderTransactionCaptureRefundPositionCollection::class;
    }

    protected function getParentDefinitionClass(): ?string
    {
        return OrderTransactionCaptureRefundDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),
            (new FkField('refund_id', 'refundId', OrderTransactionCaptureRefundDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(OrderTransactionCaptureRefundDefinition::class, 'refund_version_id'))->addFlags(new ApiAware(), new Required()),
            (new FkField('order_line_item_id', 'orderLineItemId', OrderLineItemDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(OrderLineItemDefinition::class))->addFlags(new ApiAware(), new Required()),

            (new ManyToOneAssociationField('orderLineItem', 'order_line_item_id', OrderLineItemDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('orderTransactionCaptureRefund', 'order_transaction_capture_refund.id', OrderTransactionCaptureRefundDefinition::class, 'id'))->addFlags(new ApiAware()),

            (new StringField('external_reference', 'externalReference'))->addFlags(new ApiAware()),
            (new StringField('reason', 'reason'))->addFlags(new ApiAware()),
            (new IntField('quantity', 'quantity'))->addFlags(new ApiAware()),
            (new CalculatedPriceField('amount', 'amount'))->addFlags(new ApiAware(), new Required()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
