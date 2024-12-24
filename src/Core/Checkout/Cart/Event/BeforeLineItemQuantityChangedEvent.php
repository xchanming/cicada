<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

if (Feature::isActive('v6.7.0.0')) {
    #[Package('checkout')]
    class BeforeLineItemQuantityChangedEvent implements CicadaSalesChannelEvent, CartEvent
    {
        public function __construct(
            protected readonly LineItem $lineItem,
            protected readonly Cart $cart,
            protected readonly SalesChannelContext $salesChannelContext,
            protected readonly int $beforeUpdateQuantity
        ) {
        }

        public function getLineItem(): LineItem
        {
            return $this->lineItem;
        }

        public function getCart(): Cart
        {
            return $this->cart;
        }

        public function getContext(): Context
        {
            return $this->salesChannelContext->getContext();
        }

        public function getSalesChannelContext(): SalesChannelContext
        {
            return $this->salesChannelContext;
        }

        public function getBeforeUpdateQuantity(): int
        {
            return $this->beforeUpdateQuantity;
        }
    }
} else {
    #[Package('checkout')]
    class BeforeLineItemQuantityChangedEvent implements CicadaSalesChannelEvent, CartEvent
    {
        protected int $beforeUpdateQuantity;

        /**
         * @var LineItem
         *
         * @deprecated tag:v6.7.0 - Will be natively typed
         */
        protected $lineItem;

        /**
         * @var Cart
         *
         * @deprecated tag:v6.7.0 - Will be natively typed
         */
        protected $cart;

        /**
         * @var SalesChannelContext
         *
         * @deprecated tag:v6.7.0 - Will be natively typed
         */
        protected $salesChannelContext;

        /**
         * @deprecated tag:v6.7.0 - $beforeUpdateQuantity property will be added and all properties will be readonly
         */
        public function __construct(
            LineItem $lineItem,
            Cart $cart,
            SalesChannelContext $salesChannelContext
        ) {
            $this->lineItem = $lineItem;
            $this->cart = $cart;
            $this->salesChannelContext = $salesChannelContext;
        }

        public function getLineItem(): LineItem
        {
            return $this->lineItem;
        }

        public function getCart(): Cart
        {
            return $this->cart;
        }

        public function getContext(): Context
        {
            return $this->salesChannelContext->getContext();
        }

        public function getSalesChannelContext(): SalesChannelContext
        {
            return $this->salesChannelContext;
        }

        public function getBeforeUpdateQuantity(): int
        {
            return $this->beforeUpdateQuantity;
        }

        /**
         * @deprecated tag:v6.7.0 - $beforeUpdateQuantity property will be set in constructor
         */
        public function setBeforeUpdateQuantity(int $beforeUpdateQuantity): void
        {
            Feature::triggerDeprecationOrThrow('v6.7.0.0', '$beforeUpdateQuantity property will be set in constructor');
            $this->beforeUpdateQuantity = $beforeUpdateQuantity;
        }
    }
}
