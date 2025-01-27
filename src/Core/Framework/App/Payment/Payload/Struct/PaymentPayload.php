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

#[Package('checkout')]
class PaymentPayload implements PaymentPayloadInterface
{
    use CloneTrait;
    use JsonSerializableTrait {
        jsonSerialize as protected traitJsonSerialize;
    }
    use RemoveAppTrait;

    protected Source $source;

    protected OrderTransactionEntity $orderTransaction;

    /**
     * @deprecated tag:v6.7.0 - will be removed, use `requestData` instead
     *
     * @var mixed[]
     */
    protected array $queryParameters;

    /**
     * @param mixed[] $requestData
     */
    public function __construct(
        OrderTransactionEntity $orderTransaction,
        protected OrderEntity $order,
        protected array $requestData = [],
        protected ?string $returnUrl = null,
        protected ?Struct $validateStruct = null,
        protected ?RecurringDataStruct $recurring = null,
    ) {
        $this->orderTransaction = $this->removeApp($orderTransaction);

        // @deprecated tag:v6.7.0 - will be removed, use `requestData` instead
        $this->queryParameters = $requestData;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    /**
     * @return mixed[]
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getValidateStruct(): ?Struct
    {
        return $this->validateStruct;
    }

    public function getRecurring(): ?RecurringDataStruct
    {
        return $this->recurring;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $payload = $this->traitJsonSerialize();

        if (Feature::isActive('v6.7.0.0')) {
            unset($payload['queryParameters']);
        }

        return $payload;
    }
}
