---
title: Implement wishlist page for guest
issue: NEXT-12808
---
# Storefront
* Added `\Cicada\Storefront\Page\Wishlist\GuestWishlistPage`.
* Added `\Cicada\Storefront\Page\Wishlist\GuestWishlistPagelet`.
* Added new page loader `\Cicada\Storefront\Page\Wishlist\GuestWishlistPageLoader` to load `Cicada\Storefront\Page\Wishlist\GuestWishlistPage`.
* Added new page loader `\Cicada\Storefront\Page\Wishlist\GuestWishlistPageletLoader` to load `Cicada\Storefront\Page\Wishlist\GuestWishlistPagelet`.
* Added a new event `\Cicada\Storefront\Page\Wishlist\GuestWishlistPageLoaderEvent` to be fired after `Cicada\Storefront\Page\Wishlist\GuestWishlistPage` is loaded.
* Added a new event `\Cicada\Storefront\Page\Wishlist\GuestWishlistPageletLoadedEvent` to be fired after `Cicada\Storefront\Page\Wishlist\GuestWishlistPagelet` is loaded.
* Removed @LoginRequired annotation in `\Cicada\Storefront\Controller\WishlistController::index` to allow rendering wishlist page for guest.
* Added new method `getProducts` in `src/Storefront/Resources/app/storefront/src/plugin/wishlist/base-wishlist-storage.plugin.js` to get all products in the storage.
* Added new storefront js plugin `GuestWishlistPagePlugin` in `src/Storefront/Resources/app/storefront/src/plugin/wishlist/guest-wishlist-page.plugin.js` to render guest's wishlist products when user is not logged in.
* Added new twig file `src/Storefront/Resources/views/storefront/page/wishlist/meta.html.twig` to override `layout_head_title_inner` to render wishlist page's title as `Your wishlist`.
* Added new block `base_head` in `src/Storefront/Resources/views/storefront/page/wishlist/index.html.twig` to render wishlist/meta.html.twig.
* Added a conditional check in `src/Storefront/Resources/views/storefront/page/wishlist/index.html.twig` to render the wishlist product listing or guest product listing plugin.
