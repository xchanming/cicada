<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Hook;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Facade\CartFacadeHookFactory;
use Cicada\Core\Checkout\Cart\Facade\PriceFactoryFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * Triggered during the cart calculation process.
 *
 * @hook-use-case cart_manipulation
 *
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('checkout')]
class CartHook extends Hook implements CartAware
{
    final public const HOOK_NAME = 'cart';

    private readonly SalesChannelContext $salesChannelContext;

    /**
     * @internal
     */
    public function __construct(
        private readonly Cart $cart,
        SalesChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public static function getServiceIds(): array
    {
        return [
            CartFacadeHookFactory::class,
            PriceFactoryFactory::class,
            SystemConfigFacadeHookFactory::class,
        ];
    }

    public function getName(): string
    {
        if ($this->cart->getSource()) {
            return self::HOOK_NAME . '-' . $this->cart->getSource();
        }

        return self::HOOK_NAME;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
