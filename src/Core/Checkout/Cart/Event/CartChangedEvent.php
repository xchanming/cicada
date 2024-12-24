<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

if (Feature::isActive('v6.7.0.0')) {
    #[Package('checkout')]
    class CartChangedEvent extends Event implements CartEvent, CicadaSalesChannelEvent
    {
        public function __construct(
            protected readonly Cart $cart,
            protected readonly SalesChannelContext $salesChannelContext,
        ) {
        }

        public function getCart(): Cart
        {
            return $this->cart;
        }

        public function getContext(): Context
        {
            return $this->getSalesChannelContext()->getContext();
        }

        public function getSalesChannelContext(): SalesChannelContext
        {
            return $this->salesChannelContext;
        }
    }
} else {
    #[Package('checkout')]
    class CartChangedEvent extends Event implements CartEvent
    {
        /**
         * @deprecated tag:v6.7.0 - $cart property will be typed and readonly
         *
         * @var Cart
         */
        protected $cart;

        /**
         * @deprecated tag:v6.7.0 - $context property will be removed
         *
         * @var SalesChannelContext
         */
        protected $context;

        protected readonly SalesChannelContext $salesChannelContext;

        public function __construct(Cart $cart, SalesChannelContext $context)
        {
            $this->cart = $cart;
            $this->context = $context;
            $this->salesChannelContext = $context;
        }

        public function getCart(): Cart
        {
            return $this->cart;
        }

        /**
         * @deprecated tag:v6.7.0 - Use getSalesChannelContext() instead.
         */
        public function getContext(): SalesChannelContext
        {
            Feature::triggerDeprecationOrThrow(
                'v6.7.0.0',
                'Use getSalesChannelContext() instead of getContext() to get the SalesChannelContext.'
            );

            return $this->context;
        }

        public function getSalesChannelContext(): SalesChannelContext
        {
            return $this->salesChannelContext;
        }
    }
}
