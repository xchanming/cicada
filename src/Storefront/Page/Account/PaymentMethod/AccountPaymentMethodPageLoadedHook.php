<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\PaymentMethod;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the AccountPaymentMethodPage is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 * @deprecated tag:v6.7.0 - this page is removed as customer default payment method will be removed
 *
 * @final
 */
#[Package('framework')]
class AccountPaymentMethodPageLoadedHook extends PageLoadedHook
{
    use SalesChannelContextAwareTrait {
        getSalesChannelContext as private traitGetSalesChannelContext;
    }

    final public const HOOK_NAME = 'account-payment-method-page-loaded';

    public function __construct(
        private readonly AccountPaymentMethodPage $page,
        SalesChannelContext $context
    ) {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        return self::HOOK_NAME;
    }

    public function getPage(): AccountPaymentMethodPage
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        return $this->page;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        return $this->traitGetSalesChannelContext();
    }
}
