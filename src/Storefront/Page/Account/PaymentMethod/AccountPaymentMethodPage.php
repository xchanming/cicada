<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\PaymentMethod;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

/**
 * @deprecated tag:v6.7.0 - this page is removed as customer default payment method will be removed
 */
#[Package('framework')]
class AccountPaymentMethodPage extends Page
{
    /**
     * @var PaymentMethodCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethods;

    public function getPaymentMethods(): PaymentMethodCollection
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');

        return $this->paymentMethods;
    }

    public function setPaymentMethods(PaymentMethodCollection $paymentMethods): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The default payment method will be removed and the last used payment method is prioritized.');
        $this->paymentMethods = $paymentMethods;
    }
}
