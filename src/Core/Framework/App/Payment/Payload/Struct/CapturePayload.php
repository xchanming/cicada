<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Payment\Payload\Struct;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\CloneTrait;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v6.7.0 - will be removed
 *
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class CapturePayload implements PaymentPayloadInterface
{
    use CloneTrait;
    use JsonSerializableTrait {
        jsonSerialize as private traitJsonSerialize;
        convertDateTimePropertiesToJsonStringRepresentation as private traitConvertDateTimePropertiesToJsonStringRepresentation;
    }
    use RemoveAppTrait;

    protected Source $source;

    protected OrderTransactionEntity $orderTransaction;

    public function __construct(
        OrderTransactionEntity $orderTransaction,
        protected OrderEntity $order,
        protected Struct $preOrderPayment,
        protected ?RecurringDataStruct $recurring = null,
    ) {
        $this->orderTransaction = $this->removeApp($orderTransaction);
    }

    public function setSource(Source $source): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        $this->source = $source;
    }

    public function getSource(): Source
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->source;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->orderTransaction;
    }

    public function getOrder(): OrderEntity
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->order;
    }

    public function getPreOrderPayment(): Struct
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->preOrderPayment;
    }

    public function getRecurring(): ?RecurringDataStruct
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->recurring;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        return $this->traitJsonSerialize();
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function convertDateTimePropertiesToJsonStringRepresentation(array &$array): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Payment flow `capture` will be removed'
        );

        $this->traitConvertDateTimePropertiesToJsonStringRepresentation($array);
    }
}
