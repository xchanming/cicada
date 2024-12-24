---
title: Add max limit to admin api calls
issue: NEXT-27121
---

# Core

* Added a new parameter `cicada.api.store.max_limit` to limit the max result of store-api.
  * If you used before `cicada.api.max_limit` this will be used now only for admin-api.
