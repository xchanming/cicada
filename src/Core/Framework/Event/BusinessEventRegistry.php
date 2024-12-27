<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerDeletedEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerGroupRegistrationAccepted;
use Cicada\Core\Checkout\Customer\Event\CustomerGroupRegistrationDeclined;
use Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Cicada\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent;
use Cicada\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Cicada\Core\Checkout\Order\Event\OrderPaymentMethodChangedEvent;
use Cicada\Core\Content\ContactForm\Event\ContactFormEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailSentEvent;
use Cicada\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Cicada\Core\Content\Newsletter\Event\NewsletterRegisterEvent;
use Cicada\Core\Content\Newsletter\Event\NewsletterUnsubscribeEvent;
use Cicada\Core\Content\Product\SalesChannel\Review\Event\ReviewFormEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;

#[Package('services-settings')]
class BusinessEventRegistry
{
    /**
     * @var list<class-string>
     */
    private array $classes = [
        CustomerBeforeLoginEvent::class,
        CustomerLoginEvent::class,
        CustomerLogoutEvent::class,
        CustomerDeletedEvent::class,
        UserRecoveryRequestEvent::class,
        CheckoutOrderPlacedEvent::class,
        OrderPaymentMethodChangedEvent::class,
        CustomerAccountRecoverRequestEvent::class,
        CustomerDoubleOptInRegistrationEvent::class,
        CustomerGroupRegistrationAccepted::class,
        CustomerGroupRegistrationDeclined::class,
        CustomerRegisterEvent::class,
        DoubleOptInGuestOrderEvent::class,
        GuestCustomerRegisterEvent::class,
        ContactFormEvent::class,
        ReviewFormEvent::class,
        MailBeforeSentEvent::class,
        MailBeforeValidateEvent::class,
        MailSentEvent::class,
        NewsletterConfirmEvent::class,
        NewsletterRegisterEvent::class,
        NewsletterUnsubscribeEvent::class,
        ProductExportLoggingEvent::class,
    ];

    /**
     * @internal
     */
    public function __construct()
    {
    }

    /**
     * @param list<class-string> $classes
     */
    public function addClasses(array $classes): void
    {
        /** @var list<class-string> */
        $classes = array_unique(array_merge($this->classes, $classes));

        $this->classes = $classes;
    }

    /**
     * @return list<class-string>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }
}
