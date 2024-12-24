<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefundPosition;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class OrderTransactionCaptureRefundPositionEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $refundId;

    protected string $refundVersionId;

    protected string $orderLineItemId;

    protected ?string $reason = null;

    protected int $quantity;

    protected ?string $externalReference = null;

    protected CalculatedPrice $amount;

    protected ?OrderLineItemEntity $orderLineItem = null;

    protected ?OrderTransactionCaptureRefundEntity $orderTransactionCaptureRefund = null;

    public function getRefundId(): string
    {
        return $this->refundId;
    }

    public function setRefundId(string $refundId): void
    {
        $this->refundId = $refundId;
    }

    public function getOrderLineItemId(): string
    {
        return $this->orderLineItemId;
    }

    public function setOrderLineItemId(string $orderLineItemId): void
    {
        $this->orderLineItemId = $orderLineItemId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function setExternalReference(?string $externalReference): void
    {
        $this->externalReference = $externalReference;
    }

    public function getAmount(): CalculatedPrice
    {
        return $this->amount;
    }

    public function setAmount(CalculatedPrice $amount): void
    {
        $this->amount = $amount;
    }

    public function getOrderLineItem(): ?OrderLineItemEntity
    {
        return $this->orderLineItem;
    }

    public function setOrderLineItem(?OrderLineItemEntity $orderLineItem): void
    {
        $this->orderLineItem = $orderLineItem;
    }

    public function getOrderTransactionCaptureRefund(): ?OrderTransactionCaptureRefundEntity
    {
        return $this->orderTransactionCaptureRefund;
    }

    public function setOrderTransactionCaptureRefund(?OrderTransactionCaptureRefundEntity $orderTransactionCaptureRefund): void
    {
        $this->orderTransactionCaptureRefund = $orderTransactionCaptureRefund;
    }

    public function getRefundVersionId(): string
    {
        return $this->refundVersionId;
    }

    public function setRefundVersionId(string $refundVersionId): void
    {
        $this->refundVersionId = $refundVersionId;
    }
}
