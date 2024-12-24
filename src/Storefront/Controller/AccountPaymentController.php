<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\SalesChannel\AbstractChangePaymentMethodRoute;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedHook;
use Cicada\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - this page is removed as customer default payment method will be removed
 *
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class AccountPaymentController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AccountPaymentMethodPageLoader $paymentMethodPageLoader,
        private readonly AbstractChangePaymentMethodRoute $changePaymentMethodRoute
    ) {
    }

    #[Route(path: '/account/payment', name: 'frontend.account.payment.page', options: ['seo' => false], defaults: ['_loginRequired' => true, '_noStore' => true], methods: ['GET'])]
    #[Route(path: '/account/payment', name: 'frontend.account.payment.page', options: ['seo' => false], defaults: ['_noStore' => true], methods: ['GET'])]
    public function paymentOverview(Request $request, SalesChannelContext $context): Response
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        $page = $this->paymentMethodPageLoader->load($request, $context);

        $this->hook(new AccountPaymentMethodPageLoadedHook($page, $context));

        return $this->renderStorefront('@Storefront/storefront/page/account/payment/index.html.twig', ['page' => $page]);
    }

    #[Route(path: '/account/payment', name: 'frontend.account.payment.save', defaults: ['_loginRequired' => true], methods: ['POST'])]
    public function savePayment(RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        try {
            $paymentMethodId = $requestDataBag->getAlnum('paymentMethodId');

            $this->changePaymentMethodRoute->change(
                $paymentMethodId,
                $requestDataBag,
                $context,
                $customer
            );
        } catch (InvalidUuidException|PaymentException $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.' . $exception->getErrorCode()));

            return $this->forwardToRoute('frontend.account.payment.page', ['success' => false]);
        }

        $this->addFlash(self::SUCCESS, $this->trans('account.paymentSuccess'));

        return new RedirectResponse($this->generateUrl('frontend.account.payment.page'));
    }
}
