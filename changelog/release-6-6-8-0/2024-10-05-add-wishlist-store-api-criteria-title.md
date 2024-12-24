---
title: Add criteria titles to wishlist Store APIs
issue: NEXT-38728
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
---
# Core
* Changed return type of `\Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::setTitle` from `void` to `self`
* Added criteria title `wishlist::load-products` to `\Cicada\Core\Checkout\Customer\SalesChannel\LoadWishlistRoute::load`
___
# Storefront
* Added criteria title `wishlist::list` to `\Cicada\Storefront\Controller\WishlistController::ajaxList`
* Added criteria title `wishlist::page` to `\Cicada\Storefront\Page\Wishlist\WishlistPageLoader::load`
