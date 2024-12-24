---
title: Add Flat struct to flow
issue: NEXT-21089
---
# Core
* Added `flat` protected in `Cicada\Core\Content\Flow\Dispatching\Struct\Flow`.
* Added `getFlat` and `jump` public functions in `Cicada\Core\Content\Flow\Dispatching\Struct\Flow`.
* Changed `build`, `createNestedSequence`, `createNestedAction` and `createNestedIf` functions in `Cicada\Core\Content\Flow\Dispatching\FlowBuilder` to build the flat struct for flow payload.
