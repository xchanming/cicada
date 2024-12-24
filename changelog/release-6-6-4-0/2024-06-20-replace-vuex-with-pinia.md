---
title: Replace Vuex with Pinia
issue: NEXT-36700
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com
author_github: @seggewiss
---
# Administration
* Added `Cicada.Store` (Pinia) implementation
* Changed everything `Cicada.State` related to deprecated state
___
# Upgrade Information
## Transition Vuex states into Pinia Stores
1. In Pinia, there are no `mutations`. Place every mutation under `actions`.
2. `state` needs to be an arrow function returning an object: `state: () => ({})`.
3. `actions` and `getters` no longer need to use the `state` as an argument. They can access everything with correct type support via `this`.
4. Use `Cicada.Store.register` instead of `Cicada.State.registerModule`.
5. Use `Cicada.Store.unregister` instead of `Cicada.State.unregisterModule`.
6. Use `Cicada.Store.list` instead of `Cicada.State.list`.
7. Use `Cicada.Store.get` instead of `Cicada.State.get`.
___
# Next Major Version Changes
## All Vuex stores will be transitioned to Pinia
* All Cicada states will become Pinia Stores and will be available via `Cicada.Store`
