<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\CloneTrait;
use Cicada\Core\Framework\Struct\ExtendableInterface;
use Cicada\Core\Framework\Struct\ExtendableTrait;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v6.7.0 - will be removed, PaymentTransactionStruct with new payment handlers instead
 */
#[Package('checkout')]
class SyncPaymentTransactionStruct implements \JsonSerializable, ExtendableInterface
{
    use CloneTrait;
    use ExtendableTrait {
        addExtension as private traitAddExtension;
        addArrayExtension as private traitAddArrayExtension;
        addExtensions as private traitAddExtensions;
        getExtension as private traitGetExtension;
        getExtensionOfType as private traitGetExtensionOfType;
        hasExtension as private traitHasExtension;
        hasExtensionOfType as private traitHasExtensionOfType;
        getExtensions as private traitGetExtensions;
        setExtensions as private traitSetExtensions;
        removeExtension as private traitRemoveExtension;
    }
    use JsonSerializableTrait {
        jsonSerialize as private traitJsonSerialize;
        convertDateTimePropertiesToJsonStringRepresentation as private traitConvertDateTimePropertiesToJsonStringRepresentation;
    }

    public function __construct(
        protected OrderTransactionEntity $orderTransaction,
        protected OrderEntity $order,
        protected ?RecurringDataStruct $recurring = null
    ) {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->orderTransaction;
    }

    public function getOrder(): OrderEntity
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->order;
    }

    public function getRecurring(): ?RecurringDataStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->recurring;
    }

    public function isRecurring(): bool
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->recurring !== null;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitJsonSerialize();
    }

    public function addExtension(string $name, Struct $extension): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitAddExtension($name, $extension);
    }

    /**
     * @param array<string|int, mixed> $extension
     */
    public function addArrayExtension(string $name, array $extension): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitAddArrayExtension($name, $extension);
    }

    /**
     * @param Struct[] $extensions
     */
    public function addExtensions(array $extensions): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitAddExtensions($extensions);
    }

    public function getExtension(string $name): ?Struct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitGetExtension($name);
    }

    /**
     * @template T of Struct
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function getExtensionOfType(string $name, string $type): ?Struct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitGetExtensionOfType($name, $type);
    }

    public function hasExtension(string $name): bool
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitHasExtension($name);
    }

    public function hasExtensionOfType(string $name, string $type): bool
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitHasExtensionOfType($name, $type);
    }

    /**
     * @return Struct[]
     */
    public function getExtensions(): array
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');

        return $this->traitGetExtensions();
    }

    /**
     * @param Struct[] $extensions
     */
    public function setExtensions(array $extensions): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitSetExtensions($extensions);
    }

    public function removeExtension(string $name): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitRemoveExtension($name);
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function convertDateTimePropertiesToJsonStringRepresentation(array &$array): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The specific payment structs will be removed, use PaymentTransactionStruct with new payment handlers instead');
        $this->traitConvertDateTimePropertiesToJsonStringRepresentation($array);
    }
}
