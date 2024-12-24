<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginEntity;

#[Package('checkout')]
class PaymentDistinguishableNameGenerator
{
    /**
     * @internal
     *
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     */
    public function __construct(private readonly EntityRepository $paymentMethodRepository)
    {
    }

    public function generateDistinguishablePaymentNames(Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $payments = $this->getInstalledPayments($context);

            $upsertablePayments = $this->generateDistinguishableNamesPayload($payments);
            if (\count($upsertablePayments) === 0) {
                return;
            }

            $this->paymentMethodRepository->upsert($upsertablePayments, $context);
        });
    }

    private function getInstalledPayments(Context $context): PaymentMethodCollection
    {
        $criteria = new Criteria();
        $criteria
            ->addAssociation('translations')
            ->addAssociation('plugin.translations')
            ->addAssociation('appPaymentMethod.app.translations');

        return $this->paymentMethodRepository
            ->search($criteria, $context)
            ->getEntities();
    }

    /**
     * @return array<array{id: string, distinguishableName: array<string, string>}>
     */
    private function generateDistinguishableNamesPayload(PaymentMethodCollection $payments): array
    {
        $upsertablePayments = [];
        foreach ($payments as $payment) {
            $pluginOrAppEntity = $payment->getPlugin() ?? $payment->getAppPaymentMethod()?->getApp();
            if ($pluginOrAppEntity === null || $payment->getTranslations() === null) {
                continue;
            }

            $distinguishableNames = [];
            foreach ($payment->getTranslations() as $translation) {
                $languageId = $translation->getLanguageId();

                $distinguishableNames[$languageId] = $this->generatePaymentName(
                    $pluginOrAppEntity,
                    $languageId,
                    $translation->getName() ?? $payment->getTranslation('name'),
                );
            }

            $distinguishableNames = array_filter($distinguishableNames);
            if (\count($distinguishableNames) === 0) {
                continue;
            }

            $upsertablePayments[] = [
                'id' => $payment->getId(),
                'distinguishableName' => $distinguishableNames,
            ];
        }

        return $upsertablePayments;
    }

    private function generatePaymentName(
        AppEntity|PluginEntity $entity,
        string $languageId,
        string $paymentName,
    ): ?string {
        $label = $entity->getTranslations()?->filterByProperty('languageId', $languageId)->first()?->getLabel()
            ?? $entity->getTranslation('label');

        if (!\is_string($label)) {
            return null;
        }

        return \sprintf(
            '%s | %s',
            $paymentName,
            $label
        );
    }
}
