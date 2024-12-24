<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\Storefront;

use Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use Cicada\Storefront\Page\Account\RecoverPassword\AccountRecoverPasswordPage;
use Cicada\Storefront\Page\Account\RecoverPassword\AccountRecoverPasswordPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class AuthTestSubscriber implements EventSubscriberInterface
{
    public static ?StorefrontRenderEvent $renderEvent = null;

    public static ?AccountRecoverPasswordPage $page = null;

    public static ?CustomerAccountRecoverRequestEvent $customerRecoveryEvent = null;

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
            AccountRecoverPasswordPageLoadedEvent::class => 'onPageLoad',
            CustomerAccountRecoverRequestEvent::EVENT_NAME => 'onRecoverEvent',
        ];
    }

    public function onRecoverEvent(CustomerAccountRecoverRequestEvent $event): void
    {
        self::$customerRecoveryEvent = $event;
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        self::$renderEvent = $event;
    }

    public function onPageLoad(AccountRecoverPasswordPageLoadedEvent $event): void
    {
        self::$page = $event->getPage();
    }
}
