---
title: Add aggregate admin api
issue: NEXT-39736
---

# Core

* Deprecated `\Cicada\Core\System\CustomEntity\Exception\CustomEntityNotFoundException` use `\Cicada\Core\System\CustomEntity\CustomEntityException::notFound` instead

___

# API

* Added generic `/api/aggregate/{entityName}` API. It is similar to already existing `/api/search/${entityName}`, but without loading entities
