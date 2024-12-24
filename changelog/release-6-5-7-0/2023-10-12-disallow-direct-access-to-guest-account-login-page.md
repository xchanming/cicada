---
title: Disallow direct access to guest account login page
issue: NEXT-30947
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com
author_github: @lernhart
---
# Storefront
* Changed `Cicada\Storefront\Controller\AuthController::guestLoginPage` to disallow direct access. Access is only allowed via deeplink url from the checkout.
