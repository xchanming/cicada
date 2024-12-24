---
title: Expire sales channel contexts
issue: NEXT-13247 
---
# API
* Changed the sales channel contexts to expire after 1 day. The lifetime can be controlled with the container parameter `cicada.api.store.context_lifetime` and is defined in [`cicada.yaml`](../../src/Core/Framework/Resources/config/packages/cicada.yaml). Loading an expired context will result in a new token. On login the cart is still restored and merged with the current cart.
