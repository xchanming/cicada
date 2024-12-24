<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\PaymentMethod;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - this page is removed as customer default payment method will be removed
 */
#[Package('storefront')]
class AccountPaymentMethodPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var AccountPaymentMethodPage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        AccountPaymentMethodPage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): AccountPaymentMethodPage
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        return $this->page;
    }
}
