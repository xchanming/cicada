<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PaymentMethodTranslationEntity>
 */
#[Package('checkout')]
class PaymentMethodTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getPaymentMethodIds(): array
    {
        return $this->fmap(fn (PaymentMethodTranslationEntity $paymentMethodTranslation) => $paymentMethodTranslation->getPaymentMethodId());
    }

    public function filterByPaymentMethodId(string $id): self
    {
        return $this->filter(fn (PaymentMethodTranslationEntity $paymentMethodTranslation) => $paymentMethodTranslation->getPaymentMethodId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (PaymentMethodTranslationEntity $paymentMethodTranslation) => $paymentMethodTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (PaymentMethodTranslationEntity $paymentMethodTranslation) => $paymentMethodTranslation->getLanguageId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'payment_method_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return PaymentMethodTranslationEntity::class;
    }
}
