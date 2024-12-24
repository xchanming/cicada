<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\SalesChannel;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Event\AfterLineItemRemovedEvent;
use Cicada\Core\Checkout\Cart\Event\BeforeLineItemRemovedEvent;
use Cicada\Core\Checkout\Cart\Event\CartChangedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class CartItemRemoveRoute extends AbstractCartItemRemoveRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CartCalculator $cartCalculator,
        private readonly AbstractCartPersister $cartPersister
    ) {
    }

    public function getDecorated(): AbstractCartItemRemoveRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/checkout/cart/line-item', name: 'store-api.checkout.cart.remove-item', methods: ['DELETE'])]
    #[Route(path: '/store-api/checkout/cart/line-item/delete', name: 'store-api.checkout.cart.remove-item-v2', methods: ['POST'])]
    public function remove(Request $request, Cart $cart, SalesChannelContext $context): CartResponse
    {
        $ids = $request->get('ids');
        $lineItems = [];

        foreach ($ids as $id) {
            if (!\is_string($id)) {
                throw CartException::lineItemNotFound((string) $id);
            }

            $lineItem = $cart->get($id);

            if (!$lineItem) {
                throw CartException::lineItemNotFound($id);
            }
            $lineItems[] = $lineItem;

            $cart->remove($id);

            $this->eventDispatcher->dispatch(new BeforeLineItemRemovedEvent($lineItem, $cart, $context));

            $cart->markModified();
        }

        $cart = $this->cartCalculator->calculate($cart, $context);
        $this->cartPersister->save($cart, $context);

        $this->eventDispatcher->dispatch(new AfterLineItemRemovedEvent($lineItems, $cart, $context));
        $this->eventDispatcher->dispatch(new CartChangedEvent($cart, $context));

        return new CartResponse($cart);
    }
}
