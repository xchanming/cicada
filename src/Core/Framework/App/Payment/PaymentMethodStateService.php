<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Payment;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class PaymentMethodStateService
{
    public function __construct(private readonly EntityRepository $paymentMethodRepository)
    {
    }

    public function activatePaymentMethods(string $appId, Context $context): void
    {
        $this->updatePaymentMethods($appId, $context, false, true);
    }

    public function deactivatePaymentMethods(string $appId, Context $context): void
    {
        $this->updatePaymentMethods($appId, $context, true, false);
    }

    private function updatePaymentMethods(string $appId, Context $context, bool $currentActiveState, bool $newActiveState): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appPaymentMethod.appId', $appId));
        $criteria->addFilter(new EqualsFilter('active', $currentActiveState));

        /** @var array<string> $templates */
        $templates = $this->paymentMethodRepository->searchIds($criteria, $context)->getIds();

        $updateSet = array_map(fn (string $id) => ['id' => $id, 'active' => $newActiveState], $templates);

        $this->paymentMethodRepository->update($updateSet, $context);
    }
}
