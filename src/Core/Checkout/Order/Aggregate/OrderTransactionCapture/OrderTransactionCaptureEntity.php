<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\StateMachineEntity;

#[Package('checkout')]
class OrderTransactionCaptureEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $orderTransactionId;

    protected string $orderTransactionVersionId;

    protected string $stateId;

    protected ?string $externalReference = null;

    protected CalculatedPrice $amount;

    protected ?OrderTransactionEntity $transaction = null;

    protected ?StateMachineEntity $stateMachineState = null;

    protected ?OrderTransactionCaptureRefundCollection $refunds = null;

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->orderTransactionId = $orderTransactionId;
    }

    public function getStateId(): string
    {
        return $this->stateId;
    }

    public function setStateId(string $stateId): void
    {
        $this->stateId = $stateId;
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

    public function getTransaction(): ?OrderTransactionEntity
    {
        return $this->transaction;
    }

    public function setTransaction(?OrderTransactionEntity $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getStateMachineState(): ?StateMachineEntity
    {
        return $this->stateMachineState;
    }

    public function setStateMachineState(?StateMachineEntity $stateMachineState): void
    {
        $this->stateMachineState = $stateMachineState;
    }

    public function getRefunds(): ?OrderTransactionCaptureRefundCollection
    {
        return $this->refunds;
    }

    public function setRefunds(OrderTransactionCaptureRefundCollection $refunds): void
    {
        $this->refunds = $refunds;
    }

    public function getOrderTransactionVersionId(): string
    {
        return $this->orderTransactionVersionId;
    }

    public function setOrderTransactionVersionId(string $orderTransactionVersionId): void
    {
        $this->orderTransactionVersionId = $orderTransactionVersionId;
    }
}
