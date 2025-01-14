<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Payment\Payload\Struct;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\CloneTrait;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class RefundPayload implements PaymentPayloadInterface
{
    use CloneTrait;
    use JsonSerializableTrait;
    use RemoveAppTrait;

    protected Source $source;

    protected OrderTransactionCaptureRefundEntity $refund;

    public function __construct(
        OrderTransactionCaptureRefundEntity $refund,
        protected OrderEntity $order
    ) {
        if ($refund->getTransactionCapture() && $refund->getTransactionCapture()->getTransaction()) {
            $transaction = $this->removeApp($refund->getTransactionCapture()->getTransaction());
            $refund->getTransactionCapture()->setTransaction($transaction);
        }

        $this->refund = $refund;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        if ($this->refund->getTransactionCapture() && $this->refund->getTransactionCapture()->getTransaction()) {
            return $this->refund->getTransactionCapture()->getTransaction();
        }

        throw PaymentException::refundInterrupted($this->refund->getId(), 'No transaction found for refund.');
    }

    public function getRefund(): OrderTransactionCaptureRefundEntity
    {
        return $this->refund;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }
}
