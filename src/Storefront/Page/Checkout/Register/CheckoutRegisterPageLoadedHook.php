<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Register;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Hook\CartAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the CheckoutRegisterPage is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('framework')]
class CheckoutRegisterPageLoadedHook extends PageLoadedHook implements CartAware
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'checkout-register-page-loaded';

    public function __construct(
        private readonly CheckoutRegisterPage $page,
        SalesChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        if ($this->getCart()->getSource()) {
            return self::HOOK_NAME . '-' . $this->getCart()->getSource();
        }

        return self::HOOK_NAME;
    }

    public function getPage(): CheckoutRegisterPage
    {
        return $this->page;
    }

    public function getCart(): Cart
    {
        return $this->page->getCart();
    }
}
