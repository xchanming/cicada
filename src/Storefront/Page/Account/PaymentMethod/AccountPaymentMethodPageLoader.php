<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\PaymentMethod;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Cicada\Storefront\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - this page is removed as customer default payment method will be removed
 *
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class AccountPaymentMethodPageLoader
{
    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - translator will be mandatory from 6.7
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?AbstractTranslator $translator = null
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): AccountPaymentMethodPage
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        if (!$salesChannelContext->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }

        $page = $this->genericLoader->load($request, $salesChannelContext);

        $page = AccountPaymentMethodPage::createFrom($page);
        $this->setMetaInformation($page);

        if ($page->getSalesChannelPaymentMethods()) {
            $page->setPaymentMethods($page->getSalesChannelPaymentMethods()->filterByActiveRules($salesChannelContext));
        }

        $this->eventDispatcher->dispatch(
            new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    protected function setMetaInformation(AccountPaymentMethodPage $page): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        if ($this->translator !== null && $page->getMetaInformation() === null) {
            $page->setMetaInformation(new MetaInformation());
        }

        if ($this->translator !== null) {
            $page->getMetaInformation()?->setMetaTitle(
                $this->translator->trans('account.paymentMetaTitle') . ' | ' . $page->getMetaInformation()->getMetaTitle()
            );
        }
    }
}
